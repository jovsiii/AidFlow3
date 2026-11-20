import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { runInNewContext } from 'node:vm';

test('refreshes on live changes, exports fresh data, and preserves data on failure', async () => {
    const template = readFileSync(join('resources', 'views', 'features', 'family-profiles', 'info-board.blade.php'), 'utf8');
    const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace("@json(route('family-profiles.info-board.data'))", "'/board-data'");
    const elements = {};
    const handlers = {};
    function element() {
        return {
            children: [], textContent: '', hidden: false,
            classList: { add() {}, remove() {} },
            appendChild(child) { this.children.push(child); },
            replaceChildren(fragment) { this.children = fragment.children; },
            addEventListener(name, callback) { this[name] = callback; },
        };
    }
    for (const id of ['board-rows', 'board-sector-rows', 'board-age-rows', 'board-status', 'board-as-of', 'board-families', 'board-individuals', 'board-note', 'board-export']) {
        elements[id] = element();
    }
    let interval;
    let calls = 0;
    let saved;
    const tables = [];
    let pages = 1;
    let confirmation;
    let confirmResult = false;
    let submissions = 0;
    let timeout;
    let failNext = false;
    elements['export-and-delete-form'] = { requestSubmit() { submissions++; } };
    const pdfText = [];
    let release;
    const document = {
        hidden: false,
        getElementById: (id) => elements[id],
        createDocumentFragment: element,
        createElement: element,
        addEventListener(name, callback) { handlers[name] = callback; },
    };
    const fetch = async () => {
        calls++;
        if (calls === 2) await new Promise((resolve) => { release = resolve; });
        if (calls === 5 || failNext) throw new Error('offline');
        return {
            ok: true,
            headers: { get: () => 'application/json' },
            json: async () => ({
                asOf: '2026-01-01T00:00:00+08:00',
                rows: [{ barangay: 'Batis', evacuationCenter: 'School', families: calls, individuals: calls * 3, missingHouseholdSizes: 1 }],
                demographics: {
                    sectors: [
                        { label: 'Senior Citizen', female: 0, male: 1, total: 1 },
                        { label: 'Children:', heading: true, female: 0, male: 0, total: 0 },
                        { label: '0-6 years old', female: 1, male: 0, total: 1 },
                        { label: 'TOTAL', female: 1, male: 1, total: 2, grandTotal: true },
                    ],
                    ageCategories: [
                        { label: 'Infant 00-11 month old', female: 1, male: 0, total: 1 },
                        { label: 'TOTAL', female: 1, male: 1, total: 2, grandTotal: true },
                    ],
                    incomplete: 1,
                },
            }),
        };
    };
    class Pdf {
        static API = { autoTable() {} };
        internal = { pageSize: { getHeight: () => 297, getWidth: () => 210 }, getNumberOfPages: () => pages };
        setFontSize() {}
        setFont() {}
        setTextColor() {}
        setFillColor() {}
        setDrawColor() {}
        roundedRect() {}
        text(value) { pdfText.push(value); }
        autoTable(options) { tables.push(options); options.didDrawPage({ pageNumber: 1 }); }
        addPage() { pages++; }
        save(name) { saved = name; }
    }
    runInNewContext(script, {
        document, fetch, Intl, Date,
        window: {
            jspdf: { jsPDF: Pdf },
            confirm(message) { confirmation = message; return confirmResult; },
            setTimeout(callback, delay) { assert.equal(delay, 750); timeout = callback; },
        },
        console: { error() {} },
        setInterval: (callback, delay) => { assert.equal(delay, 15000); interval = callback; },
    });
    await new Promise(setImmediate);
    assert.equal(elements['board-families'].textContent, '1');
    assert.equal(elements['board-individuals'].textContent, '3 *');
    assert.equal(elements['board-sector-rows'].children[0].children[0].textContent, 'Senior Citizen');
    assert.equal(elements['board-age-rows'].children[0].children[0].textContent, 'Infant 00-11 month old');
    assert.match(elements['board-note'].textContent, /exclude 1 profile/);
    assert.match(elements['board-note'].textContent, /Demographic totals exclude 1 person/);
    assert.match(elements['board-as-of'].textContent, /^As of: /);
    assert.doesNotMatch(elements['board-as-of'].textContent, /Philippine time/);

    handlers['family-board-connection']({ detail: true });
    assert.match(elements['board-note'].textContent, /Live updates connected/);
    handlers['family-board-change']();
    await new Promise(setImmediate);
    handlers['family-board-change']();
    release();
    await new Promise(setImmediate);
    assert.equal(calls, 3);
    assert.equal(elements['board-families'].textContent, '3');
    assert.equal(elements['board-rows'].children[0].children[3].textContent, '9 *');

    await elements['board-export'].click();
    assert.equal(calls, 4);
    assert.equal(saved, 'family-profile-info-board.pdf');
    assert.deepEqual(Array.from(tables[0].head[0]), ['Barangay (address)', 'Evacuation center', 'Families', 'Individuals']);
    assert.deepEqual(Array.from(tables[0].body[0]), ['Batis', 'School', '4', '12 *']);
    assert.equal(tables[0].foot[0][0].content, 'GRAND TOTAL');
    assert.equal(tables[0].foot[0][0].colSpan, 2);
    assert.deepEqual(Array.from(tables[0].foot[0]).slice(1), ['4', '12 *']);
    assert.deepEqual(Array.from(tables[1].head[0]), ['Category', 'F', 'M', 'TOTAL']);
    assert.equal(tables[1].body[1][0].content, 'Children:');
    assert.equal(tables[2].body[0][0], 'Infant 00-11 month old');
    assert.ok(pdfText.includes('TOTAL FAMILIES'));
    assert.ok(pdfText.includes('TOTAL INDIVIDUALS'));
    assert.ok(pdfText.includes(elements['board-as-of'].textContent));
    assert.ok(pdfText.every((text) => !text.includes('Philippine time')));
    assert.equal(elements['board-families'].textContent, '4');
    assert.equal(submissions, 0);
    timeout();
    assert.match(confirmation, /totals only, not a backup/);
    assert.equal(submissions, 0);

    interval();
    await new Promise(setImmediate);
    assert.equal(elements['board-families'].textContent, '4');
    assert.match(elements['board-status'].textContent, /Unable to refresh/);
    assert.equal(elements['board-export'].disabled, true);

    interval();
    await new Promise(setImmediate);
    Pdf.API.autoTable = undefined;
    timeout = null;
    await elements['board-export'].click();
    assert.match(elements['board-status'].textContent, /Unable to export the PDF/);
    assert.equal(elements['board-export'].disabled, false);
    assert.equal(timeout, null);
    assert.equal(submissions, 0);

    Pdf.API.autoTable = () => {};
    confirmResult = true;
    await elements['board-export'].click();
    assert.equal(submissions, 0);
    timeout();
    assert.equal(submissions, 1);

    timeout = null;
    failNext = true;
    await elements['board-export'].click();
    assert.match(elements['board-status'].textContent, /Unable to refresh/);
    assert.equal(elements['board-export'].disabled, true);
    assert.equal(timeout, null);
    assert.equal(submissions, 1);
});

test('watches the family profile database and reports connection failures', () => {
    const template = readFileSync(join('resources', 'views', 'features', 'family-profiles', 'info-board.blade.php'), 'utf8');
    const script = template.match(/<script type="module">([\s\S]*?)<\/script>/)[1]
        .replace(/^ *import .*;$/gm, '')
        .replace("@json(config('firebase.family_profiles_database_url'))", "'https://example.test'");
    const listeners = {};
    const events = [];
    runInNewContext(script, {
        initializeApp: (config) => { assert.equal(config.databaseURL, 'https://example.test'); return {}; },
        getDatabase: () => ({}),
        ref: (_, path) => path,
        onValue: (path, success, error) => { listeners[path] = { success, error }; },
        document: { dispatchEvent: (event) => events.push(event) },
        Event: class { constructor(type) { this.type = type; } },
        CustomEvent: class { constructor(type, options) { this.type = type; this.detail = options.detail; } },
        console: { error() {} },
    });

    listeners['.info/connected'].success({ val: () => true });
    listeners.familyProfiles.success();
    listeners.familyProfiles.success();
    assert.equal(events.filter((event) => event.type === 'family-board-change').length, 2);
    assert.equal(events.filter((event) => event.type === 'family-board-connection').at(-1).detail, true);
    listeners.familyProfiles.error(new Error('permission denied'));
    assert.equal(events.at(-1).detail, false);
});
