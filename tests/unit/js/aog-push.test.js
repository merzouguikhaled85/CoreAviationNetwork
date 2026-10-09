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
        unregisterUrl: '/push/unregister', requestsUrl: '/mro-requests', logoUrl: '/logo/CAN.png' };
    const button = { disabled: true, attributes: {},
        setAttribute(name, value) { this.attributes[name] = value; },
        addEventListener: (_, callback) => { button.click = callback; } };
    const status = {};
    const elements = { 'aog-push-settings': { dataset: { settings: JSON.stringify(settings) } },
        'aog-push-toggle': button, 'aog-push-status': status, 'aog-push-state': {} };
    function node(tag) {
        return { tagName: tag, children: [], attributes: {}, listeners: {},
            setAttribute(name, value) { this.attributes[name] = value; },
            addEventListener(name, callback) { this.listeners[name] = callback; },
            append(...children) { children.forEach((child) => this.appendChild(child)); },
            appendChild(child) {
                child.parent = this; this.children.push(child);
                if (child.id) elements[child.id] = child;
            },
            get firstElementChild() { return this.children[0]; },
            remove() {
                if (this.parent) this.parent.children = this.parent.children.filter((child) => child !== this);
                if (this.id) delete elements[this.id];
            }
        };
    }
    const body = node('body');
    const messaging = {
        getToken: async (args) => { assert.equal(args.vapidKey, 'public-key'); events.push('get-token'); return 'device-token'; },
        deleteToken: async () => { events.push('delete-token'); },
        onMessage: (callback) => { messaging.receive = callback; }
    };
    let prompts = 0;
    let registrations = 0;
    const context = {
        document: { getElementById: (id) => elements[id], querySelector: () => ({ content: 'csrf-value' }),
            createElement: node, body: body },
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
    return { button, status, requests, events, storage, elements, messaging,
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
    assert.equal(fresh.button.textContent, 'Turn off alerts');
    assert.equal(fresh.button.attributes['aria-pressed'], 'true');
    assert.equal(fresh.elements['aog-push-state'].textContent, 'Enabled');
    await fresh.button.click();
    assert.ok(fresh.events.indexOf('/push/unregister') < fresh.events.indexOf('delete-token'));
    assert.equal(fresh.storage.has('can.aogPushAccount'), false);
    assert.equal(fresh.button.textContent, 'Enable alerts');
    assert.equal(fresh.button.attributes['aria-pressed'], 'false');
    assert.equal(fresh.elements['aog-push-state'].textContent, 'Disabled');

    fresh.messaging.receive({ notification: { title: '<script>untrusted</script>' } });
    let stack = fresh.elements['can-aog-notifications'];
    assert.equal(stack.children.length, 1);
    let card = stack.children[0];
    assert.equal(card.attributes['aria-live'], 'polite');
    assert.equal(card.children[0].children[0].src, '/logo/CAN.png');
    assert.equal(card.children[1].textContent, 'New AOG Request');
    assert.equal(card.children[3].children[0].href, '/mro-requests');
    for (let i = 0; i < 3; i++) fresh.messaging.receive();
    assert.equal(stack.children.length, 3, 'A burst must not obscure the entire page');
    card = stack.children[0];
    card.children[3].children[1].listeners.click();
    assert.equal(stack.children.length, 2);
    while (stack.children.length) stack.children[0].children[0].children[2].listeners.click();
    assert.equal(fresh.elements['can-aog-notifications'], undefined);

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
    console.log('AOG push browser checks passed (consent, renewal, account switch, disable, iOS, branded cards).');
})().catch((error) => { console.error(error); process.exitCode = 1; });
