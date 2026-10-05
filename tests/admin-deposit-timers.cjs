const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const page = fs.readFileSync(`${__dirname}/../stock-vest.php`, 'utf8');
const start = page.indexOf("const depositTimers = document.querySelectorAll('[data-unlock-at]');");
const end = page.indexOf("const buttons = document.querySelectorAll('.toggle-details');", start);
const code = page.slice(start, end).replace(/<\?php echo wp_json_encode\(\$timer_now \* 1000\); \?>/, '1000000');
function timer(unlockAt) {
  const card = {classList: {toggle(name, value) { card.unlocked = value; }}};
  return {dataset: {unlockAt}, closest: () => card, card};
}
const timers = [timer(1000 + 86400 + 3661), timer(1002), timer(900)];
let elapsed = 0;
let tick;
vm.runInNewContext(code, {
  document: {querySelectorAll: selector => selector === '[data-unlock-at]' ? timers : []},
  performance: {now: () => elapsed},
  setInterval: (fn, ms) => { assert.equal(ms, 1000); tick = fn; },
});
assert.equal(timers[0].textContent, 'Available in 1d 1h 1m 1s');
assert.equal(timers[1].textContent, 'Available in 0d 0h 0m 2s');
assert.equal(timers[2].textContent, 'Lock period ended');
elapsed = 2000;
tick();
assert.equal(timers[1].textContent, 'Lock period ended');
assert.equal(timers[1].card.unlocked, true);
assert.equal(timers[0].textContent, 'Available in 1d 1h 0m 59s');
elapsed = 86401999;
tick();
assert.equal(timers[1].card.hidden, false);
elapsed = 86402000;
tick();
assert.equal(timers[1].card.hidden, true);
assert.equal(timers[0].card.hidden, false);
console.log('PASS: stacked countdowns, unlock transition, and hiding exactly 24 hours after unlock.');
