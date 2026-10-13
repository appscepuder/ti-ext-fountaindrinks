<?php

declare(strict_types=1);

namespace Cepuder\FountainDrinks\Http\Controllers;

use Cepuder\FountainDrinks\Classes\Codes;
use Cepuder\FountainDrinks\Classes\SpireToken;
use Cepuder\FountainDrinks\Models\Code;
use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Cart\Models\Menu;
use Igniter\Flame\Exception\FlashException;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Admin page: the Spire licence (upload, details, remove), the fountain drinks on the menu, and the
 * latest issued codes for audits.
 */
class FountainDrinks extends AdminController
{
    protected null|string|array $requiredPermissions = 'Cepuder.FountainDrinks';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('fountaindrinks', 'restaurant');
    }

    public function index(): string
    {
        Template::setTitle(lang('cepuder.fountaindrinks::default.page_title'));
        Template::setHeading(lang('cepuder.fountaindrinks::default.page_title'));

        $licence = Codes::licence();
        $this->vars['licence'] = $licence;
        $this->vars['allowedSizes'] = $licence ? SpireToken::allowedSizes($licence['tier']) : [];
        $this->vars['drinks'] = Menu::whereNotNull('spire_size')->where('spire_size', '<>', '')
            ->orderBy('menu_name')->get(['menu_id', 'menu_name', 'spire_size', 'spire_sugar', 'spire_flavors', 'menu_status']);
        $this->vars['codes'] = Code::orderByDesc('id')->limit(50)->get();

        return $this->makeView('index');
    }

    public function index_onUploadLicence(): RedirectResponse
    {
        $file = request()->file('licence_file');
        throw_unless($file && $file->isValid(), new FlashException(lang('cepuder.fountaindrinks::default.error_choose_file')));
        try {
            $licence = SpireToken::parseLicense((string)file_get_contents($file->getRealPath()));
        } catch (InvalidArgumentException $ex) {
            throw new FlashException(lang('cepuder.fountaindrinks::default.'.$ex->getMessage()));
        }
        Codes::saveLicence($licence);
        flash()->success(sprintf(lang('cepuder.fountaindrinks::default.alert_licence_saved'), $licence['secret_id']));

        return $this->redirectBack();
    }

    public function index_onRemoveLicence(): RedirectResponse
    {
        Codes::saveLicence(null);
        flash()->success(lang('cepuder.fountaindrinks::default.alert_licence_removed'));

        return $this->redirectBack();
    }
}
