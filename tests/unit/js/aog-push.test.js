/* Vérifications sans navigateur réel et sans connexion à Firebase. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../../web/js/aog-push.js'), 'utf8');
const settle = async () => { await new Promise(setImmediate); await new Promise(setImmediate); };

async function browser(options = {}) {
    const requests = [];
    const events = [];
    const storage = new Map();
    if (options.savedAccount) storage.set('can.aogPushAccount', options.savedAccount);
    const settings = { account: 'mro:1', config: {}, vapidKey: 'public-key',
        scope: '/', workerUrl: '/push/worker', registerUrl: '/push/register',
        unregisterUrl: '/push/unregister', requestsUrl: '/mro-requests' };
    const button = { disabled: true, addEventListener: (_, callback) => { button.click = callback; } };
    const status = {};
    const elements = { 'aog-push-settings': { dataset: { settings: JSON.stringify(settings) } },
        'aog-push-toggle': button, 'aog-push-status': status };
    const messaging = {
        getToken: async (args) => { assert.equal(args.vapidKey, 'public-key'); events.push('get-token'); return 'device-token'; },
        deleteToken: async () => { events.push('delete-token'); },
        onMessage: (callback) => { messaging.receive = callback; }
    };
    let prompts = 0;
    let registrations = 0;
    const context = {
        document: { getElementById: (id) => elements[id], querySelector: () => ({ content: 'csrf-value' }) },
        window: { isSecureContext: true, matchMedia: () => ({ matches: false }) },
        navigator: { userAgent: options.ios ? 'iPhone' : 'Test Browser', serviceWorker: {
            register: async (url, args) => {
                registrations++;
                assert.equal(url, '/push/worker'); assert.equal(args.scope, '/'); return { active: true };
            }
        } },
        Notification: { permission: options.permission || 'default', requestPermission: async () => {
            prompts++; return options.result || 'granted';
        } },
        localStorage: { getItem: (key) => storage.get(key) || null, setItem: (key, value) => storage.set(key, value),
            removeItem: (key) => storage.delete(key) },
        firebase: { initializeApp: () => ({ messaging: () => messaging }),
            messaging: { isSupported: async () => options.supported !== false } },
        fetch: async (url, args) => {
            requests.push({ url, args }); events.push(url);
            return { ok: true, json: async () => ({ success: true }) };
        },
        URLSearchParams, setTimeout, clearTimeout
    };
    context.window.firebase = context.firebase;
    context.window.Notification = context.Notification;
    vm.runInNewContext(source, context);
    await settle();
    return { button, status, requests, events, storage,
        prompts: () => prompts, registrations: () => registrations };
}

(async () => {
    const fresh = await browser();
    assert.equal(fresh.prompts(), 0, 'No permission prompt on page load');
    assert.equal(fresh.registrations(), 0, 'No subscription before consent');
    assert.equal(fresh.button.disabled, false);
    await fresh.button.click();
    assert.equal(fresh.prompts(), 1);
    assert.equal(fresh.requests[0].url, '/push/register');
    assert.equal(fresh.requests[0].args.credentials, 'same-origin');
    assert.equal(fresh.requests[0].args.headers['X-CSRF-Token'], 'csrf-value');
    assert.equal(fresh.requests[0].args.body, 'token=device-token&account=mro%3A1');
    assert.equal(fresh.storage.get('can.aogPushAccount'), 'mro:1');
    assert.equal(fresh.button.textContent, 'Disable AOG notifications');
    await fresh.button.click();
    assert.ok(fresh.events.indexOf('/push/unregister') < fresh.events.indexOf('delete-token'));
    assert.equal(fresh.storage.has('can.aogPushAccount'), false);
    assert.equal(fresh.button.textContent, 'Enable AOG notifications');

    const denied = await browser({ result: 'denied' });
    await denied.button.click();
    assert.equal(denied.requests.length, 0);
    assert.match(denied.status.textContent, /blocked/);

    const renewed = await browser({ permission: 'granted', savedAccount: 'mro:1' });
    assert.equal(renewed.prompts(), 0);
    assert.equal(renewed.requests.length, 1, 'Same consenting account renews its own device');

    const switched = await browser({ permission: 'granted', savedAccount: 'mro:2' });
    assert.equal(switched.requests.length, 0, 'Another account must opt in explicitly');

    const unsupported = await browser({ supported: false });
    assert.equal(unsupported.button.disabled, true);
    assert.equal(unsupported.prompts(), 0);

    const iphone = await browser({ supported: false, ios: true });
    assert.match(iphone.status.textContent, /Home Screen/);
    console.log('AOG push browser checks passed (consent, renewal, account switch, disable, iOS).');
})().catch((error) => { console.error(error); process.exitCode = 1; });
