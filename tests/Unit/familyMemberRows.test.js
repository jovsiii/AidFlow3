import assert from 'node:assert/strict';
import test from 'node:test';
import { createFamilyMemberRows } from '../../public/js/familyMemberRows.js';

function setup() {
    const body = {
        children: [],
        appendChild(row) { this.children.push(row); },
        replaceChildren() { this.children = []; },
    };
    const template = {
        content: {
            firstElementChild: {
                cloneNode() {
                    const number = {};
                    const fields = ['fullName', 'sex'].map(name => ({
                        name: `familyMembers[__INDEX__][${name}]`,
                        value: '',
                        label: `Family member __NUMBER__ ${name}`,
                        getAttribute() { return this.label; },
                        setAttribute(key, value) { this.label = value; },
                    }));
                    return {
                        hidden: false,
                        fields,
                        number,
                        querySelector() { return number; },
                        querySelectorAll() { return fields; },
                    };
                },
            },
        },
    };
    const householdSize = {
        value: '',
        listeners: {},
        addEventListener(event, callback) { this.listeners[event] = callback; },
    };
    return { body, householdSize, rows: createFamilyMemberRows(body, template, householdSize) };
}

test('rows follow household size, preserving hidden data until a decrease is confirmed', () => {
    const { body, householdSize, rows } = setup();
    rows.reset(8, []);
    assert.equal(body.children.filter(row => !row.hidden).length, 8);
    assert.equal(body.children[7].fields[0].name, 'familyMembers[7][fullName]');
    assert.equal(body.children[7].fields[0].label, 'Family member 8 fullName');
    body.children[7].fields[0].value = 'Maria';
    householdSize.value = '3';
    householdSize.listeners.input();
    assert.equal(body.children.filter(row => !row.hidden).length, 3);
    assert.equal(body.children[7].fields[0].disabled, true);
    assert.equal(rows.hasHiddenData(), true);
    householdSize.value = '8';
    householdSize.listeners.input();
    assert.equal(body.children[7].fields[0].value, 'Maria');
    assert.equal(body.children[7].fields[0].disabled, false);
    assert.equal(rows.hasHiddenData(), false);
    householdSize.value = '100';
    householdSize.listeners.change();
    assert.equal(body.children.filter(row => !row.hidden).length, 100);
    assert.equal(body.children[99].fields[0].name, 'familyMembers[99][fullName]');
});

test('existing extra members are retained as hidden rows and reset between profiles', () => {
    const { body, rows } = setup();
    rows.reset(2, { 7: { fullName: 'Maria' } });
    assert.equal(body.children.length, 8);
    assert.equal(body.children[7].hidden, true);
    rows.reset(1, []);
    assert.equal(body.children.length, 1);
    assert.equal(rows.hasHiddenData(), false);
});
