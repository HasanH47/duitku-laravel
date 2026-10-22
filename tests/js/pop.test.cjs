const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const test = require('node:test');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/js/duitku-pop.js'), 'utf8');

function mount(checkout) {
    let onClick;
    const events = [];
    const button = {
        dataset: { duitkuReference: "REF');globalThis.injected=true;//" },
        disabled: false,
        dispatchEvent(event) { events.push(event); },
    };
    const context = {
        document: { addEventListener(name, listener) { onClick = listener; } },
        window: { checkout },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
    };
    vm.runInNewContext(source, context);

    return { button, events, context, click() { onClick({ target: { closest: () => button } }); } };
}

test('passes an untrusted-looking reference as data and forwards checkout events', () => {
    let received;
    const view = mount({ process(reference, options) { received = reference; options.successEvent({ resultCode: '00' }); } });
    view.click();

    assert.equal(received, view.button.dataset.duitkuReference);
    assert.equal(view.context.injected, undefined);
    assert.equal(view.events[0].type, 'duitku:success');
    assert.equal(view.events[0].detail.resultCode, '00');
});

test('reports a missing provider script without throwing or submitting a form', () => {
    const view = mount(undefined);
    view.click();

    assert.equal(view.events[0].type, 'duitku:error');
});

test('does not open checkout for a disabled button', () => {
    let calls = 0;
    const view = mount({ process() { calls += 1; } });
    view.button.disabled = true;
    view.click();

    assert.equal(calls, 0);
});
