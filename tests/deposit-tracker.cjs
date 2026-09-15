const assert = require('node:assert/strict');
const {createTracker} = require('../pages/assets/js/investment/deposit-page.js');
const flush = () => new Promise(resolve => setImmediate(resolve));

async function run() {
    const tasks = [];
    const states = [];
    const errors = [];
    let state = {status: 'pending', elapsed_seconds: 0};
    let failure;
    const options = {
        schedule: (callback, delay) => { tasks.push({callback, delay}); return tasks.length; },
        cancel() {},
        check: async () => { if (failure) throw failure; return state; },
        onStatus: value => states.push(value.status),
        onError: (error, terminal) => errors.push(terminal),
    };
    createTracker(options);
    await flush();
    assert.equal(tasks[0].delay, 5000);
    state = {status: 'pending', elapsed_seconds: 1900};
    await tasks.shift().callback();
    assert.deepEqual(states, ['pending', 'pending']);
    assert.equal(tasks.length, 1, 'Keep polling after 30 minutes');
    failure = new Error('Offline');
    await tasks.shift().callback();
    assert.equal(tasks[0].delay, 10000);
    assert.deepEqual(errors, [false]);
    failure = null;
    state = {status: 'approved'};
    await tasks.shift().callback();
    assert.equal(states.at(-1), 'approved');
    assert.equal(tasks.length, 0, 'Stop immediately after approval');

    state = {status: 'declined'};
    createTracker(options);
    await flush();
    assert.equal(states.at(-1), 'declined');
    assert.equal(tasks.length, 0, 'Stop after decline');
    failure = Object.assign(new Error('Expired session'), {status: 401});
    createTracker(options);
    await flush();
    assert.equal(errors.at(-1), true);
    assert.equal(tasks.length, 0, 'Pause on expired authentication');

    let resolve;
    const before = states.length;
    const tracker = createTracker({...options, check: () => new Promise(done => { resolve = done; })});
    await flush();
    assert.equal(tasks.length, 0, 'No overlapping poll during a slow request');
    tracker.stop();
    resolve({status: 'approved'});
    await flush();
    assert.equal(states.length, before, 'Ignore responses after leaving the page');
    console.log('PASS: pending, 30-minute overrun, approval, decline, retry, expired session, serial polling, and cleanup');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
