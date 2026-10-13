# cepuder-pos-tastyigniter

Fountain Drinks + QR for TastyIgniter (online ordering): a Spire code per fountain drink on paid orders, shown on the customer's phone. Work in progress.

- The extension is the repo root (`composer.json`, `src/`, `resources/`, `database/`, `routes/`). On Kundera it is installed from `/data/tastyigniter/ext/ti-ext-fountaindrinks` through a Composer path repository (`composer require cepuder/ti-ext-fountaindrinks:@dev`), then `php artisan igniter:up`.
- `tools/ti-tests.mjs`: drives the demo in headless Chrome (licence upload, a guest order, screenshots).

- `tools/seed-demo.php`: the Cepuder Demo Café on a fresh TastyIgniter (Kundera, https://ti.kundera.cepuder.com). Run on the server: `php tools/seed-demo.php /data/tastyigniter/src`.
- `tools/demo-img/`: menu pictures (same as the Dolibarr demo), drawn by `render.mjs`.
