import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupFamilySummary } from '../../public/js/familyProfileSummary.js';

function setup(t, responses) {
    const originals = { document: globalThis.document, fetch: globalThis.fetch, setInterval: globalThis.setInterval };
    t.after(() => Object.assign(globalThis, originals));
    function element() {
        return {
            children: [], hidden: false, textContent: '',
            appendChild(child) { this.children.push(child); },
            replaceChildren(child) { this.children = child.isFragment ? child.children : [child]; },
        };
    }
    const elements = Object.fromEntries(['status', 'head', 'body', 'count', 'asOf'].map(key => [key, element()]));
    globalThis.document = {
        hidden: false,
        createElement: element,
        createDocumentFragment: () => ({ ...element(), isFragment: true }),
        addEventListener() {},
    };
    let interval;
    globalThis.setInterval = (callback, delay) => { assert.equal(delay, 15000); interval = callback; };
    let calls = 0;
    globalThis.fetch = async (_url, options) => {
        assert.equal(options.cache, 'no-store');
        const result = responses[calls++];
        if (result instanceof Error) throw result;
        return { ok: true, headers: { get: () => 'application/json' }, json: async () => result };
    };
    setupFamilySummary(elements, '/summary');
    return { elements, interval: () => interval(), calls: () => calls };
}

test('displays one row per family and refreshes the horizontal table', async t => {
    const data = {
        asOf: '2026-01-01T00:00:00+08:00',
        profiles: [
            { fullName: 'Maria Santos', householdSize: 2, fourPsBeneficiary: false, familyMembers: [{ fullName: 'Ana Santos', age: 0 }] },
            { headOfFamily: 'Juan Cruz', legacyField: 'Preserved' },
        ],
    };
    const fresh = { ...data, profiles: [{ ...data.profiles[0], householdSize: 3 }, data.profiles[1]] };
    const context = setup(t, [data, fresh, { ...data, profiles: [] }]);
    await new Promise(setImmediate);
    const { elements } = context;
    const headers = elements.head.children[0].children.map(cell => cell.textContent);
    const rows = elements.body.children.map(row => row.children.map(cell => cell.textContent));
    assert.equal(headers[0], 'Family head');
    assert.equal(rows.length, 2);
    assert.equal(rows[0][0], 'Maria Santos');
    assert.equal(rows[1][0], 'Juan Cruz');
    assert.equal(rows[0][headers.indexOf('Family Members / 1 / Full Name')], 'Ana Santos');
    assert.equal(rows[0][headers.indexOf('Family Members / 1 / Age')], '0');
    assert.equal(rows[0][headers.indexOf('Four Ps Beneficiary')], 'No');
    assert.equal(rows[1][headers.indexOf('Legacy Field')], 'Preserved');
    assert.equal(rows[0][headers.indexOf('Legacy Field')], '-');
    assert.equal(elements.count.textContent, 'Families: 2');

    context.interval();
    await new Promise(setImmediate);
    assert.equal(context.calls(), 2);
    assert.equal(elements.body.children[0].children[headers.indexOf('Household Size')].textContent, '3');

    context.interval();
    await new Promise(setImmediate);
    assert.equal(elements.body.children.length, 0);
    assert.match(elements.status.textContent, /No family profiles/);
});

test('reports refresh errors, preserves displayed data, and recovers', async t => {
    const data = { asOf: '2026-01-01T00:00:00+08:00', profiles: [{ fullName: 'Maria' }] };
    const oldError = console.error;
    console.error = () => {};
    t.after(() => { console.error = oldError; });
    const context = setup(t, [data, new Error('offline'), data]);
    await new Promise(setImmediate);
    context.interval();
    await new Promise(setImmediate);
    assert.match(context.elements.status.textContent, /retrying automatically/);
    assert.equal(context.elements.body.children[0].children[0].textContent, 'Maria');

    context.interval();
    await new Promise(setImmediate);
    assert.equal(context.elements.status.hidden, true);
    assert.equal(context.elements.body.children[0].children[0].textContent, 'Maria');
});
