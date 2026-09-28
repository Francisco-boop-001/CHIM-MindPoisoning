'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../server/dashboard.js'), 'utf8');

function deferred() {
    let resolve;
    let reject;
    const promise = new Promise((resolvePromise, rejectPromise) => {
        resolve = resolvePromise;
        reject = rejectPromise;
    });
    return { promise, resolve, reject };
}

function fixture(fetch) {
    const timers = new Map();
    const listeners = new Map();
    const regions = ['last-updated', 'logs-source', 'database-source', 'notices', 'interactions'].map(name => ({
        localName: 'section',
        children: [{ name }],
        childNodes: [],
        getAttribute(attribute) { return attribute === 'data-dashboard-refresh-region' ? name : null; },
        querySelectorAll() { return []; },
        contains() { return false; },
    }));
    let nextTimer = 0;
    const controls = { hidden: true };
    const toggle = {
        attributes: new Map(),
        addEventListener() {},
        setAttribute(name, value) { this.attributes.set(name, value); },
    };
    const status = { textContent: '' };
    const document = {
        hidden: false,
        activeElement: null,
        querySelector(selector) { return selector === '[data-dashboard-refresh-controls]' ? controls : null; },
        querySelectorAll(selector) { return selector === '[data-dashboard-refresh-region]' ? regions : []; },
        getElementById(id) {
            return id === 'dashboard-refresh-toggle' ? toggle
                : id === 'dashboard-refresh-status' ? status : null;
        },
        addEventListener(name, callback) { listeners.set(name, callback); },
    };
    const window = {
        location: {
            href: 'http://example.test/dashboard.php?tab=interactions&q=Eowyn%20%26%20Mira&outcome=committed&download=1',
            origin: 'http://example.test',
        },
        scrollX: 0,
        scrollY: 0,
        getSelection() { return null; },
        scrollTo() {},
    };
    const context = {
        AbortController,
        URL,
        document,
        window,
        fetch,
        setTimeout(callback, delay) {
            const id = ++nextTimer;
            timers.set(id, { callback, delay });
            return id;
        },
        clearTimeout(id) { timers.delete(id); },
    };
    vm.runInNewContext(source, context, { filename: 'server/dashboard.js' });
    return { controls, document, listeners, regions, status, timers, toggle };
}

function runTimer(app, delay) {
    const matches = [...app.timers.entries()].filter(([, timer]) => timer.delay === delay);
    assert.equal(matches.length, 1, `expected one timer at ${delay}ms`);
    const [id, timer] = matches[0];
    app.timers.delete(id);
    timer.callback();
}

async function drain() {
    await new Promise(resolve => setImmediate(resolve));
}

async function main() {
    const pending = deferred();
    let requestUrl;
    let requestOptions;
    let requests = 0;
    const app = fixture((url, options) => {
        requests++;
        requestUrl = url;
        requestOptions = options;
        return pending.promise;
    });
    const originalChildren = app.regions[0].children;
    assert.equal(app.controls.hidden, false);
    runTimer(app, 5000);
    assert.equal(requests, 1);
    const requestedPage = new URL(requestUrl);
    assert.equal(requestedPage.searchParams.get('tab'), 'interactions');
    assert.equal(requestedPage.searchParams.get('q'), 'Eowyn & Mira');
    assert.equal(requestedPage.searchParams.get('outcome'), 'committed');
    assert.equal(requestedPage.searchParams.has('download'), false);
    assert.equal(requestOptions.method, 'GET');
    assert.equal(requestOptions.credentials, 'same-origin');
    assert.equal(requestOptions.cache, 'no-store');
    assert.equal(app.timers.size, 1);
    assert.deepEqual([...app.timers.values()].map(timer => timer.delay), [15000], 'poll was scheduled while a request was pending');

    pending.resolve({ ok: false, headers: { get() { return 'text/html'; } } });
    await drain();
    assert.equal(app.status.textContent, 'Refresh failed; showing the last successful data.');
    assert.equal(app.regions[0].children, originalChildren, 'failed response replaced existing data');
    assert.equal([...app.timers.values()].some(timer => timer.delay === 5000), true, 'next poll was not scheduled after completion');

    let timeoutOptions;
    const timeoutApp = fixture((_url, options) => {
        timeoutOptions = options;
        return new Promise((_resolve, reject) => {
            options.signal.addEventListener('abort', () => reject(new Error('aborted')), { once: true });
        });
    });
    const timeoutChildren = timeoutApp.regions[0].children;
    runTimer(timeoutApp, 5000);
    runTimer(timeoutApp, 15000);
    await drain();
    assert.equal(timeoutOptions.signal.aborted, true);
    assert.equal(timeoutApp.status.textContent, 'Refresh timed out; showing the last successful data.');
    assert.equal(timeoutApp.regions[0].children, timeoutChildren, 'timeout replaced existing data');

    const hiddenRequest = deferred();
    let hiddenOptions;
    const hiddenApp = fixture((_url, options) => {
        hiddenOptions = options;
        return hiddenRequest.promise;
    });
    const hiddenChildren = hiddenApp.regions[0].children;
    runTimer(hiddenApp, 5000);
    hiddenApp.document.hidden = true;
    hiddenApp.listeners.get('visibilitychange')();
    assert.equal(hiddenOptions.signal.aborted, true, 'hiding the page did not abort its request');
    hiddenRequest.resolve({
        ok: true,
        headers: { get() { return 'text/html'; } },
        async text() { return '<html>stale response</html>'; },
    });
    await drain();
    assert.equal(hiddenApp.regions[0].children, hiddenChildren, 'stale hidden-tab response replaced existing data');
    assert.equal(hiddenApp.status.textContent, 'Automatic refresh is on.', 'stale hidden-tab response was treated as a refresh failure');
    assert.equal(hiddenApp.timers.size, 0, 'hidden page scheduled another poll');

    process.stdout.write('dashboard_refresh_test: PASS (filtered GET, no overlap, failure/timeout retention, hidden-tab stale response)\n');
}

main().catch(error => {
    process.stderr.write(`${error.stack || error}\n`);
    process.exitCode = 1;
});
