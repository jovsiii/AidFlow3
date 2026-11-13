import assert from 'node:assert/strict';
import test from 'node:test';
import { createCmecDetails, getCmecDetails } from '../../public/js/cmecDetails.js';

test('CMEC details list each tent with its current status and date', () => {
    const center = { tents: Object.fromEntries(Array.from({ length: 50 }, (_, i) =>
        [`CMEC-${String(i + 1).padStart(3, '0')}`, ''])) };
    const details = getCmecDetails(center, [
        { tentId: 'CMEC-001', date: '11/05/2026' },
        { tentId: 'CDJ-001', date: '11/06/2026' },
    ]);
    assert.equal(details.total, 50);
    assert.equal(details.occupied, 1);
    assert.equal(details.available, 49);
    assert.equal(details.percent, '2.00');
    assert.deepEqual(details.tents[0], { tentId: 'CMEC-001', occupied: true, date: '11/05/2026' });
    assert.deepEqual(details.tents[1], { tentId: 'CMEC-002', occupied: false, date: '--' });
    assert.equal(getCmecDetails({ tents: {} }, []).percent, '0.00');
});

test('opening CMEC details renders the tent table, totals and occupancy chart', () => {
    const makeElement = () => ({
        textContent: '',
        style: {},
        children: [],
        classes: new Set(),
        listeners: {},
        classList: {
            add(...names) { names.forEach(name => this.owner.classes.add(name)); },
            remove(...names) { names.forEach(name => this.owner.classes.delete(name)); },
            contains(name) { return this.owner.classes.has(name); },
        },
        addEventListener(name, callback) { this.listeners[name] = callback; },
        appendChild(child) { this.children.push(child); },
        replaceChildren() { this.children = []; },
        focus() { globalThis.document.activeElement = this; },
    });
    const elements = {};
    for (const id of ['cmec-details-modal', 'close-cmec-details', 'cmec-total-tents',
        'cmec-occupied-tents', 'cmec-available-tents', 'cmec-occupancy-percent',
        'cmec-occupancy-circle', 'cmec-tent-table']) {
        const element = makeElement();
        element.classList.owner = element;
        elements[id] = element;
    }
    elements['cmec-details-modal'].classList.add('hidden');
    const originalDocument = globalThis.document;
    const focused = makeElement();
    globalThis.document = {
        activeElement: focused,
        getElementById: id => elements[id],
        createElement: makeElement,
        addEventListener() {},
    };
    try {
        const center = { tents: { 'CMEC-001': '', 'CMEC-002': '' } };
        const open = createCmecDetails(center, [{ tentId: 'CMEC-001', date: '11/05/2026' }]);
        open();
        assert.equal(elements['cmec-total-tents'].textContent, 2);
        assert.equal(elements['cmec-occupied-tents'].textContent, 1);
        assert.equal(elements['cmec-available-tents'].textContent, 1);
        assert.equal(elements['cmec-occupancy-percent'].textContent, '50.00%');
        assert.deepEqual(elements['cmec-tent-table'].children.map(row => row.children.map(cell => cell.textContent)), [
            ['11/05/2026', 'CMEC-001', 'Occupied'],
            ['--', 'CMEC-002', 'Available'],
        ]);
        assert.equal(elements['cmec-details-modal'].classList.contains('hidden'), false);
        elements['close-cmec-details'].listeners.click();
        assert.equal(elements['cmec-details-modal'].classList.contains('hidden'), true);
        assert.equal(globalThis.document.activeElement, focused);
    } finally {
        globalThis.document = originalDocument;
    }
});
