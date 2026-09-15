// Browser smoke test against local fixtures; no WordPress account or payment is touched.
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const http = require('node:http');
const {spawn} = require('node:child_process');
const assert = require('node:assert/strict');

async function run() {
    const browser = process.env.WSI_TEST_BROWSER || 'C:/Program Files/Google/Chrome/Application/chrome.exe';
    const root = path.resolve(__dirname, '..');
    const template = fs.readFileSync(path.join(root, 'pages/deposit.php'), 'utf8');
    const form = template.match(/<form data-wsi-deposit-page[\s\S]*?<\/form>/)[0].replace(/<\?php[\s\S]*?\?>/g, '');
    const signup = fs.readFileSync(path.join(root, 'pages/signup.php'), 'utf8').match(/<form[\s\S]*?<\/form>/)[0].replace(/<\?php[\s\S]*?\?>/g, '');
    const dialog = template.match(/<dialog[\s\S]*?<\/dialog>/)[0];
    let status = 'pending';
    let pending = null;
    let elapsed = 0;
    let submissions = 0;
    const config = {
        ajax_url: '/ajax', dashboard_url: '/dashboard', status_nonce: 'fixture-nonce',
        exchange_rate: 1000, min_invest: 50,
        wallets: {usdt_trc: {label: 'USDT (TRC20)', address: 'TEST-WALLET', instruction: 'Test instruction'}},
    };
    const server = http.createServer(async (req, res) => {
        const url = new URL(req.url, 'http://localhost');
        if (url.pathname === '/ajax') {
            let body = '';
            for await (const chunk of req) body += chunk;
            if (body.includes('wsi_submit_deposit')) submissions++;
            res.setHeader('Content-Type', 'application/json');
            return res.end(JSON.stringify({success: true, data: {deposit_id: 42, amount_usd: 50, status, elapsed_seconds: elapsed, estimated_seconds: 1800}}));
        }
        if (url.pathname === '/signup') {
            res.setHeader('Content-Type', 'text/html; charset=utf-8');
            return res.end(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app435e.css"><link rel="stylesheet" href="/assets/css/ui-polish.css"></head><body class="wsi-ui theme-blue" style="font-family:Arial;background:#eef3f8;padding:24px"><main class="card adminuiux-card mx-auto" style="max-width:480px"><div class="card-body"><h2>Create your account</h2><p class="text-secondary mb-4">Enter your details to get started.</p>${signup}</div></main></body></html>`);
        }
        if (url.pathname === '/dashboard') return res.end('<h1>Dashboard fixture</h1>');
        if (url.pathname.startsWith('/assets/')) {
            const file = path.resolve(root, 'pages', '.' + url.pathname);
            if (!file.startsWith(path.join(root, 'pages', 'assets') + path.sep)) { res.statusCode = 404; return res.end(); }
            res.setHeader('Content-Type', file.endsWith('.css') ? 'text/css' : file.endsWith('.js') ? 'text/javascript' : 'application/octet-stream');
            return fs.createReadStream(file).on('error', () => { res.statusCode = 404; res.end(); }).pipe(res);
        }
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        res.end(`<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="/assets/css/app435e.css"><link rel="stylesheet" href="/assets/css/deposit-page.css"><link rel="stylesheet" href="/assets/css/ui-polish.css"></head><body class="wsi-ui theme-blue" style="font-family:Arial;background:#eaf0f6;padding:32px"><h1>Deposit</h1><p id="wsi-deposit-feedback" hidden></p>${form}${dialog}<script id="wsi-deposit-config" type="application/json">${JSON.stringify({...config, pending_deposit: pending})}</script><script src="/assets/js/investment/deposit-page.js" defer></script></body></html>`);
    });
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const base = `http://127.0.0.1:${server.address().port}`;
    const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'wsi-deposit-browser-'));
    const processHandle = spawn(browser, ['--headless=new', '--no-sandbox', '--disable-gpu', '--no-first-run', '--no-default-browser-check', '--remote-debugging-port=0', `--user-data-dir=${directory}`, 'about:blank'], {windowsHide: true, stdio: ['ignore', 'ignore', 'pipe']});
    let launchError;
    let browserLog = '';
    processHandle.stderr.on('data', chunk => { browserLog += chunk.toString(); });
    processHandle.on('error', error => { launchError = error; });
    let socket;
    let send;
    const pause = ms => new Promise(resolve => setTimeout(resolve, ms));
    async function until(fn, message, timeout = 10000) {
        const deadline = Date.now() + timeout;
        while (Date.now() < deadline) { if (await fn()) return; await pause(100); }
        throw new Error(message);
    }
    try {
        const portFile = path.join(directory, 'DevToolsActivePort');
        await until(() => { if (launchError) throw launchError; return fs.existsSync(portFile); }, 'Browser did not start');
        const port = fs.readFileSync(portFile, 'utf8').split('\n')[0];
        const target = await (await fetch(`http://127.0.0.1:${port}/json/new?about:blank`, {method: 'PUT'})).json();
        socket = new WebSocket(target.webSocketDebuggerUrl);
        await new Promise((resolve, reject) => { socket.onopen = resolve; socket.onerror = reject; });
        let id = 0;
        const calls = new Map();
        const browserErrors = [];
        socket.onmessage = event => {
            const data = JSON.parse(event.data);
            if (data.method === 'Runtime.exceptionThrown') browserErrors.push(data.params.exceptionDetails.text);
            if (calls.has(data.id)) {
                const {resolve, reject} = calls.get(data.id); calls.delete(data.id);
                data.error ? reject(new Error(data.error.message)) : resolve(data.result);
            }
        };
        send = (method, params = {}) => new Promise((resolve, reject) => {
            const callId = ++id;
            const timeout = setTimeout(() => { calls.delete(callId); reject(new Error('Browser timeout: ' + method + '\n' + browserLog.slice(-1500))); }, 8000);
            calls.set(callId, {resolve: value => { clearTimeout(timeout); resolve(value); }, reject: error => { clearTimeout(timeout); reject(error); }});
            socket.send(JSON.stringify({id: callId, method, params}));
        });
        const evaluate = async expression => {
            const result = await send('Runtime.evaluate', {expression, returnByValue: true, awaitPromise: true});
            if (result.exceptionDetails) throw new Error(result.exceptionDetails.text);
            return result.result.value;
        };
        const navigate = async () => {
            await send('Page.navigate', {url: base + '/'});
            await until(() => evaluate('document.readyState === "complete" && !!document.getElementById("wsi-deposit-wait")'), 'Page did not load');
        };
        await send('Page.enable'); await send('Runtime.enable');
        await send('Emulation.setDeviceMetricsOverride', {width: 1280, height: 900, deviceScaleFactor: 1, mobile: false});
        await navigate();
        await evaluate(`document.getElementById('amount_naira').value = '50000'; document.getElementById('amount_naira').dispatchEvent(new Event('input', {bubbles:true})); document.getElementById('wsi-deposit-form').requestSubmit(); document.getElementById('wsi-deposit-form').requestSubmit();`);
        await until(() => evaluate('document.getElementById("wsi-deposit-wait").open'), 'Waiting popup did not open');
        assert.equal(submissions, 1, 'Repeated submission is guarded');
        assert.match(await evaluate('location.search'), /deposit_id=42/);
        assert.match(await evaluate('document.getElementById("wsi-deposit-wait-clock").textContent'), /29:|30:00/);
        assert.equal(await evaluate('document.getElementById("wsi_deposit_submit").disabled'), true);
        const desktop = path.join(directory, 'deposit-desktop.png');
        fs.writeFileSync(desktop, Buffer.from((await send('Page.captureScreenshot', {format: 'png'})).data, 'base64'));
        await send('Emulation.setDeviceMetricsOverride', {width: 390, height: 844, deviceScaleFactor: 1, mobile: true});
        assert.equal(await evaluate('document.getElementById("wsi-deposit-wait").getBoundingClientRect().width <= innerWidth'), true);
        const mobile = path.join(directory, 'deposit-mobile.png');
        fs.writeFileSync(mobile, Buffer.from((await send('Page.captureScreenshot', {format: 'png'})).data, 'base64'));
        status = 'approved';
        await until(() => evaluate('location.pathname === "/dashboard"'), 'Approval did not redirect');

        status = 'pending'; elapsed = 1900;
        pending = {deposit_id: 42, amount_usd: 50, elapsed_seconds: elapsed, estimated_seconds: 1800};
        await navigate();
        await until(() => evaluate('document.getElementById("wsi-deposit-wait")?.open'), 'Pending deposit did not resume');
        assert.equal(await evaluate('document.getElementById("wsi-deposit-wait-clock").textContent'), 'Still pending');
        assert.equal(await evaluate('location.pathname'), '/');
        status = 'declined';
        await until(() => evaluate('document.getElementById("wsi-deposit-wait").dataset.state === "declined"'), 'Decline did not stop waiting');
        await evaluate('document.getElementById("wsi-deposit-wait-close").click()');
        assert.equal(await evaluate('document.getElementById("wsi-deposit-wait").open'), false);
        await send('Page.navigate', {url: base + '/signup'});
        await until(() => evaluate('document.readyState === "complete" && !!document.getElementById("namef")'), 'Signup fixture did not load');
        await evaluate('document.getElementById("namef").value = "Alex"; document.getElementById("emailadd").focus()');
        await pause(250);
        const signupMobile = path.join(directory, 'signup-mobile.png');
        fs.writeFileSync(signupMobile, Buffer.from((await send('Page.captureScreenshot', {format: 'png', captureBeyondViewport: true})).data, 'base64'));
        await send('Emulation.setDeviceMetricsOverride', {width: 1280, height: 1000, deviceScaleFactor: 1, mobile: false});
        const signupDesktop = path.join(directory, 'signup-desktop.png');
        fs.writeFileSync(signupDesktop, Buffer.from((await send('Page.captureScreenshot', {format: 'png'})).data, 'base64'));
        console.log(`Signup screenshots: ${signupMobile} | ${signupDesktop}`);
        assert.equal(browserErrors.length, 0, browserErrors.join('\n'));
        console.log('PASS: browser submission, duplicate guard, popup, desktop/mobile sizing, admin approval redirect, refresh resume, 30-minute overrun, and decline');
        console.log(`Desktop screenshot: ${desktop}\nMobile screenshot: ${mobile}`);
    } finally {
        if (socket) socket.close();
        processHandle.kill();
        server.closeAllConnections(); server.close();
        // Keep screenshots in the temporary directory for review.
    }
}
run().catch(error => { console.error(error); process.exitCode = 1; });
