<?php

declare(strict_types=1);

namespace Cepuder\FountainDrinks\Classes;

use Cepuder\FountainDrinks\Models\Code;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Igniter\Cart\Models\Menu;
use Igniter\Cart\Models\Order;
use Igniter\System\Models\Settings;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Licence, minting and QR rendering, shared by the event listeners, the storefront block, the email and
 * the admin page.
 */
class Codes
{
    public const string LICENCE_PREF = 'cepuder_fountaindrinks_licence';

    /** Gateways where the money comes in at pickup: codes wait until staff complete the order. */
    public const array PAY_LATER_GATEWAYS = ['cod'];

    /**
     * The store licence uploaded on the admin page, or null.
     *
     * @return array{key: string, iv: string, tier: string, customer: string, secret_id: string}|null
     */
    public static function licence(): ?array
    {
        $stored = Settings::getPref(self::LICENCE_PREF);
        if (!$stored) {
            return null;
        }
        try {
            return json_decode(decrypt($stored), true) ?: null;
        } catch (Throwable) {
            return null;
        }
    }

    public static function saveLicence(?array $licence): void
    {
        Settings::setPref(self::LICENCE_PREF, $licence ? encrypt(json_encode($licence)) : null);
    }

    public static function paysLater(Order $order): bool
    {
        return in_array((string)$order->payment, self::PAY_LATER_GATEWAYS, true);
    }

    /**
     * Fountain drink lines of an order: [order menu, menu item] pairs with a Spire size.
     *
     * @return array<int, array{0: mixed, 1: Menu}>
     */
    public static function drinkLines(Order $order): array
    {
        $lines = $order->getOrderMenus();
        $menus = Menu::whereIn('menu_id', $lines->pluck('menu_id')->filter()->unique())
            ->whereNotNull('spire_size')->where('spire_size', '<>', '')
            ->get()->keyBy('menu_id');

        $out = [];
        foreach ($lines as $line) {
            if (($menu = $menus->get($line->menu_id)) && $line->quantity > 0) {
                $out[] = [$line, $menu];
            }
        }

        return $out;
    }

    /**
     * Issue one code per fountain drink, once per order (later calls do nothing). Never throws: a
     * failure must not stop the order, the cashier warning shows it instead.
     */
    public static function mintForOrder(Order $order): int
    {
        try {
            $licence = self::licence();
            if (!$licence) {
                Log::warning('Fountain Drinks + QR: no Spire licence, no codes for order '.$order->order_id);

                return 0;
            }
            if (Code::where('order_id', $order->order_id)->exists()) {
                return 0;
            }
            $allowed = SpireToken::allowedSizes($licence['tier']);

            return DB::transaction(function() use ($order, $licence, $allowed): int {
                $nb = 0;
                foreach (self::drinkLines($order) as [$line, $menu]) {
                    if (!in_array($menu->spire_size, $allowed, true)) {
                        Log::warning('Fountain Drinks + QR: '.$menu->spire_size.' not in licence tier '.$licence['tier'].', no code for order '.$order->order_id);

                        continue;
                    }
                    for ($n = 0; $n < $line->quantity; $n++) {
                        $token = SpireToken::mint($licence['key'], $licence['iv'], $menu->spire_size, (bool)$menu->spire_sugar, (bool)$menu->spire_flavors);
                        Code::create([
                            'order_id' => $order->order_id,
                            'order_menu_id' => $line->order_menu_id,
                            'menu_id' => $menu->menu_id,
                            'label' => mb_substr((string)($line->name ?: $menu->menu_name), 0, 250),
                            'size' => $menu->spire_size,
                            'sugar' => (bool)$menu->spire_sugar,
                            'flavors' => (bool)$menu->spire_flavors,
                            'token_id' => $token['token_id'],
                            'issued_at' => $token['timestamp'],
                            'payload' => $token['payload'],
                            'secret_id' => $licence['secret_id'],
                            'location_id' => $order->location_id,
                        ]);
                        $nb++;
                    }
                }

                return $nb;
            });
        } catch (Throwable $ex) {
            Log::error('Fountain Drinks + QR: '.$ex->getMessage());

            return 0;
        }
    }

    /** Codes of an order, in line order. */
    public static function forOrder(int $orderId): Collection
    {
        return Code::where('order_id', $orderId)->orderBy('id')->get();
    }

    /** Why a paid order has fewer codes than fountain drinks, for staff, or null when all are there. */
    public static function missingReason(Order $order, int $nbCodes): ?string
    {
        $nbDrinks = 0;
        $sizes = [];
        foreach (self::drinkLines($order) as [$line, $menu]) {
            $nbDrinks += $line->quantity;
            $sizes[$menu->spire_size] = $menu->spire_size;
        }
        if ($nbCodes >= $nbDrinks) {
            return null;
        }
        if (!$licence = self::licence()) {
            return lang('cepuder.fountaindrinks::default.missing_no_licence');
        }
        if ($notAllowed = array_diff($sizes, SpireToken::allowedSizes($licence['tier']))) {
            return sprintf(lang('cepuder.fountaindrinks::default.missing_size'), implode(', ', array_map(self::sizeLabel(...), $notAllowed)));
        }

        return sprintf(lang('cepuder.fountaindrinks::default.missing_codes'), $nbCodes, $nbDrinks);
    }

    public static function sizeLabel(string $size): string
    {
        return lang('cepuder.fountaindrinks::default.size_'.strtolower($size));
    }

    /** "Pepsi Max Medium" stays as is; "Pepsi" becomes "Pepsi · Medium". */
    public static function drinkLabel(Code $code, string $sep = ' · '): string
    {
        $size = self::sizeLabel($code->size);
        $label = (string)$code->label;
        if ($label === '') {
            return $size;
        }

        return stripos($label, $size) !== false || stripos($label, $code->size) !== false ? $label : $label.$sep.$size;
    }

    /** QR as SVG markup, crisp on any screen. */
    public static function qrSvg(string $payload): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'svgAddXmlHeader' => false,
            'drawLightModules' => false,
        ])))->render($payload);
    }

    /** QR as PNG bytes, for email clients that don't show SVG. */
    public static function qrPng(string $payload): string
    {
        return (new QRCode(new QROptions([
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'scale' => 6,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
        ])))->render($payload);
    }

    /** Signed, unguessable URL of one code's PNG (for the email). */
    public static function pngUrl(Code $code): string
    {
        return URL::signedRoute('cepuder.fountaindrinks.qr', ['code' => $code->token_id]);
    }
}
