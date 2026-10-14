// node ti-tests.mjs <mode> [args]: drive the Kundera TastyIgniter demo like a user, in headless Chrome.
//   licence <config.license.json>   admin: upload the Spire licence on Sales > Fountain Drinks + QR
//   admin <path> <out.png>          admin: screenshot any admin page (e.g. cepuder/fountaindrinks/fountain_drinks)
//   order <items> <out-prefix>      storefront, phone size: guest order, "Pay at the counter";
//                                   items like "Pepsi Max Medium*2,Pepsi Large,Croissant". Prints the order page URL.
//   page <url> <out.png>            storefront, phone size: screenshot (e.g. the order page after completing it)
//   listing <url> <out.png>         same, with every drink QR swapped for random data (for store listings)
// Needs TI_PASS (admin password) for the admin modes. Screenshots go where you say.
import { spawn, execSync } from 'node:child_process';
import { randomBytes } from 'node:crypto';
import { writeFileSync } from 'node:fs';
import { resolve } from 'node:path';

const B = 'https://ti.kundera.cepuder.com';
const [mode, ...args] = process.argv.slice(2);
const port = 9340;
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', ['--headless=new', '--disable-gpu', '--hide-scrollbars',
  `--remote-debugging-port=${port}`, '--user-data-dir=/tmp/ti-tests-profile', 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let t; for (let i = 0; i < 50 && !t; i++) { await sleep(200); try { t = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); } catch {} }
const ws = new WebSocket(t.find(x => x.type === 'page').webSocketDebuggerUrl); await new Promise(r => ws.onopen = r);
let id = 0; const p = new Map(); ws.onmessage = e => { const m = JSON.parse(e.data); p.get(m.id)?.(m); };
const send = (method, params = {}) => new Promise(r => { p.set(++id, r); ws.send(JSON.stringify({ id, method, params })); });
const js = async e => { const r = await send('Runtime.evaluate', { expression: e, awaitPromise: true, returnByValue: true }); if (r.result?.exceptionDetails) console.log('JS error', r.result.exceptionDetails.exception?.description); return r.result?.result?.value; };
const go = async (url, wait = 3500) => { await send('Page.navigate', { url }); await sleep(wait); };
const size = (w, h) => send('Emulation.setDeviceMetricsOverride', { width: w, height: h, deviceScaleFactor: 1, mobile: w < 600 });
const shot = async (out, full = true) => {
  if (full) { const h = await js('document.documentElement.scrollHeight'); await size(await js('window.innerWidth'), Math.min(h, 6000)); await sleep(400); }
  const r = await send('Page.captureScreenshot', { format: 'png' });
  writeFileSync(out, Buffer.from(r.result.data, 'base64'));
  console.log('screenshot', out);
};
const text = () => js(`document.body.innerText.replace(/\\s+/g, ' ').slice(0, 600)`);
const adminLogin = async () => {
  await size(1280, 900);
  await go(`${B}/admin/login`);
  if (await js(`!!document.querySelector('input[name=email]')`)) {
    await js(`document.querySelector('input[name=email]').value='admin@cepuder.com'; document.querySelector('input[name=password]').value=${JSON.stringify(process.env.TI_PASS)};
      document.querySelector('input[name=password]').form.querySelector('[type=submit]').click(); 1`);
    await sleep(4000);
  }
  console.log('admin:', (await text()).slice(0, 80));
};

await send('Page.enable'); await send('DOM.enable');

if (mode === 'licence') {
  await adminLogin();
  await go(`${B}/admin/cepuder/fountaindrinks/fountain_drinks`);
  const doc = await send('DOM.getDocument');
  const input = await send('DOM.querySelector', { nodeId: doc.result.root.nodeId, selector: 'input[name=licence_file]' });
  await send('DOM.setFileInputFiles', { nodeId: input.result.nodeId, files: [resolve(args[0])] });
  await js(`document.querySelector('input[name=licence_file]').form.submit(); 1`); await sleep(4000);
  console.log((await text()).slice(0, 400));
} else if (mode === 'admin') {
  await adminLogin();
  await go(`${B}/admin/${args[0]}`);
  await shot(args[1]);
} else if (mode === 'order') {
  await size(390, 844);
  await go(`${B}/default/menus`, 4000);
  await js(`document.querySelector('.cookie-banner button, #cookieConsent button, [data-cookie-consent] button')?.click(); 1`);
  for (const spec of args[0].split(',')) {
    const [name, qty = 1] = spec.split('*');
    for (let n = 0; n < +qty; n++) {
      const ok = await js(`(() => { const h = [...document.querySelectorAll('.menu-name')].find(h => h.innerText.trim() === ${JSON.stringify(name.trim())});
        const card = h && h.closest('[id^=menu]'); if (card) card.click(); return !!card; })()`);
      if (!ok) console.log('NOT FOUND', name);
      await sleep(2500);
      // Items with options open a modal: confirm it
      await js(`document.querySelector('.modal.show button[type=submit], .modal.show .btn-primary')?.click(); 1`);
      await sleep(2000);
    }
  }
  await go(`${B}/checkout`, 5000);
  if (process.env.DUMP) console.log(await js(`[...document.querySelectorAll('input,select,textarea,button')].map(e => [e.tagName, e.type, e.name, e.getAttribute('wire:model'), e.value, (e.innerText||'').trim().slice(0,30)].join('|')).join('\\n')`));
  await js(`(() => { const set = (n, v) => { const e = document.querySelector('[name="' + n + '"], [wire\\\\:model="fields.' + n + '"], [wire\\\\:model\\\\.blur="fields.' + n + '"]'); if (e) { e.value = v; e.dispatchEvent(new Event('input', {bubbles: true})); e.dispatchEvent(new Event('change', {bubbles: true})); } return !!e; };
    return ['first_name', 'last_name', 'email', 'telephone'].map(n => n + ':' + set(n, {first_name: 'Demo', last_name: 'Customer', email: 'demo@cepuder.com', telephone: '+353910000000'}[n])).join(' '); })()`).then(console.log);
  // The telephone widget only takes real typing
  await js(`(() => { const t = document.querySelector('input[type=tel], .iti input[type=text], input[autocomplete=tel]') || [...document.querySelectorAll('input[type=text]')].find(i => !i.getAttribute('wire:model')); t.focus(); return !!t; })()`);
  await send('Input.insertText', { text: '851234567' });
  await js(`document.activeElement.blur(); 1`);
  await sleep(1500);
  await js(`(() => { const r = [...document.querySelectorAll('input[type=radio]')].find(r => /cod/i.test(r.value)); if (r) { r.click(); r.dispatchEvent(new Event('change', {bubbles: true})); } return !!r; })()`).then(v => console.log('pay at counter:', v));
  await sleep(1500);
  await js(`document.querySelectorAll('input[type=checkbox][name*=terms], input[type=checkbox][wire\\\\:model*=terms]').forEach(c => { if (!c.checked) c.click(); }); 1`);
  await shot(`${args[1]}-checkout.png`);
  await js(`(() => { const b = [...document.querySelectorAll('button')].find(b => /place order|confirm|checkout/i.test(b.innerText)); if (b) b.click(); return b?.innerText; })()`).then(v => console.log('clicked', v));
  await sleep(7000);
  console.log('order page:', await js('location.href'));
  await size(390, 844);
  await shot(`${args[1]}-success.png`);
} else if (mode === 'page' || mode === 'listing') {
  await size(390, 844);
  await go(args[0], 5000);
  if (mode === 'listing') {
    // Listing screenshots never show real codes: swap every QR for random data (qrencode SVGs)
    const fakes = Array.from({ length: 8 }, () => execSync(`qrencode -t SVG -m 0 -o - ${randomBytes(48).toString('base64')}`).toString().replace(/^<\?xml[^>]*>/, ''));
    console.log('swapped', await js(`(() => { const f = ${JSON.stringify(fakes)}; const s = [...document.querySelectorAll('#cepuder-drink-codes svg')];
      s.forEach((e, i) => { e.outerHTML = f[i].replace('<svg', '<svg style="width:100%;height:auto"'); }); return s.length; })()`));
    await sleep(300);
  }
  await shot(args[1]);
}
chrome.kill(); process.exit(0);
