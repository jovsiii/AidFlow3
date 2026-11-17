import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { runInNewContext } from 'node:vm';

test('refreshes the info board and keeps the last successful data on failure', async () => {
    const template = readFileSync(join('resources', 'views', 'features', 'family-profiles', 'info-board.blade.php'), 'utf8');
    const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace("@json(route('family-profiles.info-board.data'))", "'/board-data'");
    const elements = {};
    function element() {
        return {
            children: [], textContent: '', hidden: false,
            classList: { add() {}, remove() {} },
            appendChild(child) { this.children.push(child); },
            replaceChildren(fragment) { this.children = fragment.children; },
            addEventListener() {},
        };
    }
    for (const id of ['board-rows', 'board-status', 'board-as-of', 'board-families', 'board-individuals', 'board-note', 'board-refresh']) {
        elements[id] = element();
    }
    let interval;
    let calls = 0;
    const document = {
        hidden: false,
        getElementById: (id) => elements[id],
        createDocumentFragment: element,
        createElement: element,
        addEventListener() {},
    };
    const fetch = async () => {
        calls++;
        if (calls === 3) throw new Error('offline');
        return {
            ok: true,
            headers: { get: () => 'application/json' },
            json: async () => ({
                asOf: '2026-01-01T00:00:00+08:00',
                rows: [{ barangay: 'Batis', evacuationCenter: 'School', families: calls, individuals: calls * 3, missingHouseholdSizes: 1 }],
            }),
        };
    };
    runInNewContext(script, {
        document, fetch, Intl, Date,
        console: { error() {} },
        setInterval: (callback, delay) => { assert.equal(delay, 15000); interval = callback; },
    });
    await new Promise(setImmediate);
    assert.equal(elements['board-families'].textContent, '1');
    assert.equal(elements['board-individuals'].textContent, '3 *');
    assert.match(elements['board-note'].textContent, /exclude 1 profile/);
    assert.match(elements['board-as-of'].textContent, /Philippine time/);

    interval();
    await new Promise(setImmediate);
    assert.equal(elements['board-families'].textContent, '2');
    assert.equal(elements['board-rows'].children[0].children[3].textContent, '6 *');

    interval();
    await new Promise(setImmediate);
    assert.equal(elements['board-families'].textContent, '2');
    assert.match(elements['board-status'].textContent, /Unable to refresh/);
});
