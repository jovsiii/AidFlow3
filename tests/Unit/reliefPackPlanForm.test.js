import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { join } from 'node:path';
import { runInNewContext } from 'node:vm';

test('adds and removes pack items with unique form indices and enforces the limit', () => {
    const template = readFileSync(join('resources', 'views', 'features', 'inventory', 'relief-pack-plan.blade.php'), 'utf8');
    const script = template.match(/<script>([\s\S]*?)<\/script>/)[1];
    const rows = {
        children: [{}],
        addEventListener(name, callback) { this[name] = callback; },
        appendChild(row) { this.children.push(row); },
    };
    const message = { textContent: '' };
    const add = { addEventListener(name, callback) { this[name] = callback; } };
    const rowTemplate = {
        content: {
            firstElementChild: {
                cloneNode() {
                    const inputs = [{ name: 'items[__INDEX__][item]' }, { name: 'items[__INDEX__][quantity]' }];
                    return { inputs, querySelectorAll: () => inputs, querySelector: () => ({ focus() {} }) };
                },
            },
        },
    };
    const document = { getElementById: (id) => ({
        'pack-plan-items': rows,
        'pack-plan-item-template': rowTemplate,
        'pack-plan-message': message,
        'add-pack-plan-item': add,
    })[id] };
    runInNewContext(script, { document });

    add.click();
    assert.equal(rows.children[1].inputs[0].name, 'items[1][item]');
    rows.click({ target: { closest: () => ({ closest: () => ({ remove: () => rows.children.splice(1, 1) }) }) } });
    add.click();
    assert.equal(rows.children[1].inputs[1].name, 'items[2][quantity]');
    while (rows.children.length < 100) add.click();
    add.click();
    assert.equal(rows.children.length, 100);
    assert.match(message.textContent, /at most 100/);
});
