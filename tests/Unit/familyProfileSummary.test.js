import { test } from 'node:test';
import assert from 'node:assert/strict';
import { setupFamilySummaryExport } from '../../public/js/familyProfileSummary.js';

test('exports freshly fetched profile and member data as a landscape PDF table', async () => {
    const button = { addEventListener(_name, handler) { this.click = handler; } };
    const status = {};
    let pdfOptions;
    let table;
    let saved;
    let calls = 0;
    class Pdf {
        static API = { autoTable() {} };
        internal = { getNumberOfPages: () => 1, pageSize: { getWidth: () => 297, getHeight: () => 210 } };
        constructor(options) { pdfOptions = options; }
        setTextColor() {}
        setFont() {}
        setFontSize() {}
        text() {}
        autoTable(options) { table = options; options.didDrawPage(); }
        save(name) { saved = name; }
    }
    globalThis.window = { jspdf: { jsPDF: Pdf } };
    globalThis.fetch = async (_url, options) => {
        calls++;
        assert.equal(options.cache, 'no-store');
        return {
            ok: true, headers: { get: () => 'application/json' },
            json: async () => ({
                asOf: '2026-01-01T00:00:00+08:00',
                profiles: calls === 1 ? [{
                    fullName: 'Maria Santos', householdSize: 2, fourPsBeneficiary: false,
                    familyMembers: [{ fullName: 'Ana Santos', age: 0, typeOfVulnerability: 'Senior' }],
                    legacyField: 'Preserved',
                }] : [],
            }),
        };
    };
    setupFamilySummaryExport(button, status, '/summary');
    await button.click();
    assert.equal(pdfOptions.orientation, 'landscape');
    assert.deepEqual(table.head, [['Field', 'Details']]);
    assert.equal(table.body[0][0].content, 'Family 1: Maria Santos');
    assert.ok(table.body.some(row => row[0] === 'Family Members / 1 / Full Name' && row[1] === 'Ana Santos'));
    assert.ok(table.body.some(row => row[0] === 'Family Members / 1 / Age' && row[1] === '0'));
    assert.ok(table.body.some(row => row[0] === 'Four Ps Beneficiary' && row[1] === 'No'));
    assert.ok(table.body.some(row => row[0] === 'Legacy Field' && row[1] === 'Preserved'));
    assert.equal(saved, 'family-profile-summary.pdf');
    assert.equal(button.disabled, false);

    await button.click();
    assert.equal(calls, 2);
    assert.match(table.body[0][0].content, /No family profiles/);
});

test('reports export failures and allows retry', async () => {
    const button = { addEventListener(_name, handler) { this.click = handler; } };
    const status = {};
    globalThis.window = { jspdf: { jsPDF: class { static API = { autoTable() {} }; } } };
    globalThis.fetch = async () => ({ ok: false, headers: { get: () => 'application/json' } });
    const previousError = console.error;
    console.error = () => {};
    try {
        setupFamilySummaryExport(button, status, '/summary');
        await button.click();
        assert.equal(status.hidden, false);
        assert.match(status.textContent, /Unable to export/);
        assert.equal(button.disabled, false);
    } finally {
        console.error = previousError;
    }
});
