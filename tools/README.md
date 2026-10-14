# Development tools (not part of the extension)

- The extension is the repo root (`composer.json`, `src/`, `resources/`, `database/`, `routes/`). On Kundera it is installed from `/data/tastyigniter/ext/ti-ext-fountaindrinks` through a Composer path repository (`composer require cepuder/ti-ext-fountaindrinks:@dev`), then `php artisan igniter:up`.
- `ti-tests.mjs`: drives the demo in headless Chrome (licence upload, a guest order, screenshots; `listing` swaps the QRs for random data).
- `seed-demo.php`: the Cepuder Demo Café on a fresh TastyIgniter (Kundera, https://ti.kundera.cepuder.com). Run on the server: `php tools/seed-demo.php /data/tastyigniter/src`.
- `demo-img/`: menu pictures (same as the Dolibarr demo), drawn by `render.mjs`.
