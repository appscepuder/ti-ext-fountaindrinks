import { spawn } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
// node render.mjs (in this folder): one 400x400 PNG per demo product, drawn by products.html
const out = '.'; mkdirSync(out, { recursive: true });
const port = 9335;
const chrome = spawn('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome', ['--headless=new', '--disable-gpu', '--hide-scrollbars', `--remote-debugging-port=${port}`, `--user-data-dir=/tmp/demo-img-profile`, 'about:blank'], { stdio: 'ignore' });
const sleep = ms => new Promise(r => setTimeout(r, ms));
let t; for (let i = 0; i < 50 && !t; i++) { await sleep(200); try { t = await (await fetch(`http://127.0.0.1:${port}/json`)).json(); } catch {} }
const ws = new WebSocket(t.find(x => x.type === 'page').webSocketDebuggerUrl); await new Promise(r => ws.onopen = r);
let id = 0; const p = new Map(); ws.onmessage = e => { const m = JSON.parse(e.data); p.get(m.id)?.(m); };
const send = (method, params = {}) => new Promise(r => { p.set(++id, r); ws.send(JSON.stringify({ id, method, params })); });
const js = async e => (await send('Runtime.evaluate', { expression: e, returnByValue: true })).result.result.value;
await send('Emulation.setDeviceMetricsOverride', { width: 400, height: 400, deviceScaleFactor: 1, mobile: false });
await send('Page.navigate', { url: `file://${process.cwd()}/products.html` }); await sleep(1000);
for (const k of await js('window.kinds')) {
  await js(`draw(${JSON.stringify(k)})`); await sleep(100);
  const r = await send('Page.captureScreenshot', { format: 'png' });
  writeFileSync(`${out}/${k}.png`, Buffer.from(r.result.data, 'base64'));
}
chrome.kill(); process.exit(0);
