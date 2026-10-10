'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '../../..');
let handler;
let resolvePopup;
let popups = 0;
let submissions = 0;
let spinners = 0;
const form = { matches: () => true };
const context = {
    document: { addEventListener: (_, callback) => { handler = callback; } },
    window: {
        Swal: { fire: () => {
            popups++;
            return new Promise(resolve => { resolvePopup = resolve; });
        } },
        canSpinner: { show: () => { spinners++; } },
        confirm: () => false
    },
    HTMLFormElement: { prototype: { submit() {
        assert.equal(this, form);
        submissions++;
    } } }
};
vm.runInNewContext(fs.readFileSync(path.join(root, 'web/js/logout-confirmation.js'), 'utf8'), context);
const event = { target: form, preventDefault() {} };

(async () => {
    let waiting = handler(event);
    assert.equal(submissions, 0, 'Wait for explicit confirmation');
    assert.equal(spinners, 0, 'No loading overlay before confirmation');
    await handler(event);
    assert.equal(popups, 1, 'Ignore repeated submits while the dialog is open');
    resolvePopup({ isConfirmed: false });
    await waiting;
    assert.equal(submissions, 0, 'Cancel must not sign out');
    assert.equal(spinners, 0);

    waiting = handler(event);
    resolvePopup({ isDismissed: true });
    await waiting;
    assert.equal(submissions, 0, 'Dismiss must not sign out');

    waiting = handler(event);
    assert.equal(submissions, 0, 'Retry after cancel still waits');
    resolvePopup({ isConfirmed: true });
    await waiting;
    assert.equal(submissions, 1, 'Confirm submits exactly once');
    assert.equal(spinners, 1);

    context.window.Swal = undefined;
    await handler(event);
    assert.equal(submissions, 1, 'Fallback cancel must not sign out');
    await handler({ target: { matches: () => false }, preventDefault() {
        throw new Error('Unrelated form blocked');
    } });

    const header = fs.readFileSync(path.join(root, 'views/layouts/header.php'), 'utf8');
    assert.ok(!header.includes("$(this).closest('form').submit()"), 'No legacy jQuery submit bypass');
    for (const file of ['header.php', 'can-navbar.php']) {
        const view = fs.readFileSync(path.join(root, 'views/layouts', file), 'utf8');
        const forms = view.match(/Html::beginForm\(\[.*?logout-all-devices[\s\S]*?\]\)/g);
        assert.ok(forms && forms.length);
        for (const markup of forms) {
            assert.ok(markup.includes("'data-confirm-logout-all' => 'true'"));
            assert.ok(markup.includes("'data-no-spinner' => 'true'"));
        }
    }
    console.log('Logout checks passed: wait, cancel, dismiss, retry, confirm, spinner, legacy bypass.');
})().catch(error => { console.error(error); process.exitCode = 1; });
