<?php
/*
 * Demo setup on a fresh TastyIgniter (Kundera): Cepuder Demo Café in Galway, EUR, pickup only, open
 * all week, a café menu with fountain drinks, and "Pay at the counter" switched on. Clears the
 * onboarding checklist. Re-runnable. Run on the server as the files' owner:
 *   php tools/seed-demo.php /data/tastyigniter/src
 * The Spire licence is not set here (real keys never go in git).
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = rtrim($argv[1] ?? '/data/tastyigniter/src', '/');
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use Igniter\Local\Models\Location;
use Igniter\Local\Models\LocationArea;
use Igniter\PayRegister\Models\Payment;
use Igniter\System\Models\Country;
use Igniter\System\Models\Currency;
use Igniter\System\Models\Settings;
use Illuminate\Support\Facades\DB;

$prefix = DB::getTablePrefix();

// Ireland, euros, metric, Irish time
$eur = Currency::where('currency_code', 'EUR')->firstOrFail();
$eur->currency_status = 1;
$eur->save();
Currency::updateDefault($eur->getKey());
Country::updateDefault(Country::where('iso_code_2', 'IE')->value('country_id'));
Settings::set(['timezone' => 'Europe/Dublin', 'distance_unit' => 'km']);
echo "currency EUR, Ireland, Europe/Dublin\n";

// The café: map position (onboarding needs it), pickup only, open every day
$location = Location::getDefault();
$location->location_lat = 53.2722;
$location->location_lng = -9.0489;
$location->location_telephone = '+353 91 000000';
$location->save();
foreach (['delivery' => ['is_enabled' => 0], 'collection' => ['is_enabled' => 1, 'time_interval' => 5, 'lead_time' => 5]] as $item => $data) {
    DB::table('location_settings')->updateOrInsert(
        ['location_id' => $location->getKey(), 'item' => $item],
        ['data' => json_encode($data)],
    );
}
// A default area is required by onboarding, even without delivery
if (!LocationArea::where('location_id', $location->getKey())->exists()) {
    $area = new LocationArea;
    $area->location_id = $location->getKey();
    $area->name = 'Galway';
    $area->type = 'circle';
    $area->boundaries = ['circle' => json_encode(['lat' => 53.2722, 'lng' => -9.0489, 'radius' => 5000])];
    $area->conditions = [['type' => 'all', 'amount' => 0, 'total' => 0, 'priority' => 0]];
    $area->color = '#2f7de1';
    $area->is_default = 1;
    $area->save();
}
DB::table('working_hours')->where('location_id', $location->getKey())->delete();
foreach (['opening', 'delivery', 'collection'] as $type) {
    for ($day = 0; $day < 7; $day++) {
        DB::table('working_hours')->insert([
            'location_id' => $location->getKey(), 'type' => $type, 'weekday' => $day,
            'opening_time' => '00:00:00', 'closing_time' => '23:59:00', 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
echo "location: Galway, pickup only, open all week\n";

// Payment: cash at the counter (the TastyIgniter "cod" gateway)
$cod = Payment::where('code', 'cod')->firstOrFail();
$cod->name = 'Pay at the counter';
$cod->description = 'Pay when you collect your order';
$cod->status = 1;
$cod->save();
echo "payment: Pay at the counter\n";

// Menu: hide the stock demo menu, add ours
$ours = ['Fountain drinks', 'Coffee', 'Food'];
Category::whereNotIn('name', $ours)->update(['status' => 0]);
$stock = Menu::whereDoesntHave('categories', fn ($q) => $q->whereIn('name', $ours))->pluck('menu_id');
Menu::whereIn('menu_id', $stock)->update(['menu_status' => 0]);
$cats = [];
foreach ($ours as $i => $name) {
    $cat = Category::firstOrNew(['name' => $name]);
    $cat->status = 1;
    $cat->priority = $i;
    $cat->save();
    $cats[$name] = $cat->getKey();
}
// ref (picture), name, price incl. VAT, category
$menu = [
    ['PEPMAX-S', 'Pepsi Max Small', 2.50, 'Fountain drinks'],
    ['PEPMAX-M', 'Pepsi Max Medium', 3.00, 'Fountain drinks'],
    ['PEPMAX-L', 'Pepsi Max Large', 3.50, 'Fountain drinks'],
    ['PEPSI-S', 'Pepsi Small', 2.70, 'Fountain drinks'],
    ['PEPSI-M', 'Pepsi Medium', 3.20, 'Fountain drinks'],
    ['PEPSI-L', 'Pepsi Large', 3.70, 'Fountain drinks'],
    ['FREESTYLE-M', 'Any drink + flavor shot Medium', 3.60, 'Fountain drinks'],
    ['ESPRESSO', 'Espresso', 2.40, 'Coffee'],
    ['CAPPUCCINO', 'Cappuccino', 3.60, 'Coffee'],
    ['CROISSANT', 'Croissant', 2.20, 'Food'],
    ['TOASTIE', 'Ham & cheese toastie', 6.50, 'Food'],
    ['BROWNIE', 'Chocolate brownie', 3.20, 'Food'],
];
foreach ($menu as $i => [$ref, $name, $price, $catName]) {
    $item = Menu::firstOrNew(['menu_name' => $name]);
    $made = !$item->exists;
    $item->menu_price = $price;
    $item->menu_status = 1;
    $item->menu_priority = $i;
    $item->minimum_qty = 1;
    $item->save();
    $item->categories()->sync([$cats[$catName]]);
    $img = __DIR__.'/demo-img/'.$ref.'.png';
    if (is_file($img) && !$item->hasMedia('thumb')) {
        $item->newMediaInstance()->addFromFile($img, 'thumb');
    }
    echo ($made ? 'made ' : 'kept ').$name."\n";
}

// Child theme "cepuder-demo" of Orange: only the menus page differs (menu pictures on, no empty
// location photo). A child theme survives TastyIgniter updates; the vendor files don't.
$themes = resolve(Igniter\Main\Classes\ThemeManager::class);
$child = Igniter\Flame\Support\Facades\Igniter::themesPath().'/cepuder-demo';
if (!is_dir($child)) {
    $themes->createChildTheme('igniter-orange', 'cepuder-demo');
}
$page = $root.'/vendor/tastyigniter/ti-theme-orange/resources/views/_pages/local/menus.blade.php';
$src = file_get_contents($page);
$src = str_replace(
    ["'[igniter-orange::local-header]': []", "'[igniter-orange::menu-item-list]': []"],
    ["'[igniter-orange::local-header]':\n    showThumb: 0", "'[igniter-orange::menu-item-list]':\n    showThumb: 1"],
    $src,
);
@mkdir($child.'/_pages/local', 0775, true);
file_put_contents($child.'/_pages/local/menus.blade.php', $src);
Igniter\Main\Models\Theme::syncAll();
Igniter\Main\Models\Theme::activateTheme('cepuder-demo');
echo "theme: cepuder-demo (menu pictures on)\n";

// Onboarding: mail goes to the log on the demo, so mark the test mail step done
Settings::setPref('test_mail_sent', true);

echo "Done. Shop: ".config('app.url')."\n";
