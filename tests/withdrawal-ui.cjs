// Exercise the actual inline withdrawal script with browser controls and mocked network responses.
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const page = fs.readFileSync(`${__dirname}/../pages/withdrawal.php`, 'utf8');
const scripts = [...page.matchAll(/<script>([\s\S]*?)<\/script>/g)];
const code = scripts.at(-1)[1]
  .replace(/<\?php echo wp_json_encode\(\$available_balance\); \?>/g, '50')
  .replace(/<\?php echo wp_json_encode\(\$unlocked_assets\); \?>/g, '1000')
  .replace(/<\?php[\s\S]*?\?>/g, '/fixture');
const controls = {};
function control(id) {
  return controls[id] = {value: '', events: {}, addEventListener(name, fn) { this.events[name] = fn; }, setAttribute(name, value) { this[name] = value; }};
}
const form = control('wsi-withdrawal-form');
const source = control('withdrawal_source');
source.value = 'available_balance';
const amount = control('withdraw_amount');
const payout = control('withdraw_payout_method');
payout.value = 'crypto';
const bankInput = {}; const cryptoInput = {};
const bankGroup = {hasAttribute: () => true, querySelectorAll: () => [bankInput]};
const cryptoGroup = {hasAttribute: () => false, querySelectorAll: () => [cryptoInput]};
form.querySelectorAll = () => [bankGroup, cryptoGroup];
const assetsOption = {disabled: true};
const profitOption = {};
const button = {disabled: false};
source.querySelector = selector => selector.includes('total_assets') ? assetsOption : profitOption;
form.querySelector = () => button;
form.reportValidity = () => true;
for (const id of ['wsi-withdraw-assets', 'wsi-withdraw-available', 'wsi-withdraw-lock', 'wsi-withdraw-lock-message']) control(id);
const timers = [];
const requests = [];
const alerts = [];
let balance = {totalAssets: 1200, totalAssetsUnlockedAmount: 1000, totalAssetsLockedAmount: 200, available: 50, totalAssetsLocked: true, totalAssetsUnlockAt: '2027-01-01T00:00:00Z'};
let resolveSubmission;
const context = {
  document: {hidden: false, getElementById: id => controls[id], addEventListener(name, fn) { if (name === 'DOMContentLoaded') this.ready = fn; }},
  window: {location: {href: ''}},
  setInterval: fn => timers.push(fn),
  alert: message => alerts.push(message), console,
  FormData: class {constructor() { this.source = source.value; }},
  fetch: (url, options) => {
    if (options.method === 'POST') {
      requests.push(options.body);
      return new Promise(resolve => { resolveSubmission = resolve; });
    }
    return Promise.resolve({ok: true, json: async () => balance});
  },
};
const flush = () => new Promise(resolve => setImmediate(resolve));
(async () => {
  vm.runInNewContext(code, context);
  context.document.ready();
  await flush();
  assert.equal(assetsOption.disabled, true);
  payout.value = 'bank'; payout.events.change();
  assert.equal(bankInput.required, true); assert.equal(cryptoInput.disabled, true);
  payout.value = 'crypto'; payout.events.change();
  assert.equal(bankInput.disabled, true); assert.equal(cryptoInput.required, true);
  assert.equal(amount.max, '50.00');
  assert.match(controls['wsi-withdraw-lock'].className, /bi-lock-fill/);

  balance = {...balance, totalAssetsLocked: false, totalAssetsUnlockAt: null};
  timers[0]();
  await flush();
  assert.equal(assetsOption.disabled, false);
  assert.match(controls['wsi-withdraw-lock'].className, /bi-unlock-fill/);
  source.value = 'total_assets';
  source.events.change();
  assert.equal(amount.max, '1000.00');

  form.events.submit({preventDefault() {}});
  form.events.submit({preventDefault() {}});
  assert.equal(requests.length, 1);
  assert.equal(requests[0].source, 'total_assets');
  assert.equal(button.disabled, true);
  resolveSubmission({ok: true, status: 200, text: async () => JSON.stringify({success: false, data: {message: 'Insufficient balance'}})});
  await flush();
  assert.equal(button.disabled, false);
  assert.equal(alerts[0], 'Error: Insufficient balance');

  balance = {...balance, totalAssetsLocked: true};
  timers[0]();
  await flush();
  assert.equal(source.value, 'available_balance');
  assert.equal(assetsOption.disabled, true);
  assert.equal(amount.max, '50.00');
  form.events.submit({preventDefault() {}});
  resolveSubmission({ok: false, status: 500, text: async () => '<html>Server failure</html>'});
  await flush();
  assert.match(alerts.at(-1), /HTTP 500/);
  assert.equal(button.disabled, false);
  form.events.submit({preventDefault() {}});
  resolveSubmission({ok: true, status: 200, text: async () => '0'});
  await flush();
  assert.match(alerts.at(-1), /session may have expired/);
  console.log('PASS: lock/unlock refresh, account limits, source submission, repeat-click guard, and error recovery');
})().catch(error => { console.error(error); process.exitCode = 1; });
