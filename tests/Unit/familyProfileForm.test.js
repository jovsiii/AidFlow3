import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import vm from 'node:vm';

test('opening a family profile preserves the method, CSRF token and hidden checkbox defaults', async () => {
    const blade = await readFile(new URL('../../resources/views/features/family-profiles/index.blade.php', import.meta.url), 'utf8');
    let script = blade.match(/<script type="module">([\s\S]*?)<\/script>/)[1];
    script = script.replace(/import \{ createFamilyMemberRows \} from '[^']+';/, '')
        .replace("@json(route('family-profiles.update', '__PROFILE__'))", "'/features/family-profiles/__PROFILE__'")
        .replace(/@if\(old\('profileId'\) && \$errors->any\(\)\)[\s\S]*?@endif/, '');

    const fields = [
        { name: '_token', type: 'hidden', value: 'csrf-token' },
        { name: '_method', type: 'hidden', value: 'PUT' },
        { name: 'profileId', type: 'hidden', value: '' },
        { name: 'fourPsBeneficiary', type: 'hidden', value: '0' },
        { name: 'fourPsBeneficiary', type: 'checkbox', value: '1', checked: false },
        { name: 'fullName', type: 'text', value: '' },
        { name: 'householdSize', type: 'number', value: '' },
    ];
    const form = {
        elements: { namedItem: name => fields.find(field => field.name === name) },
        querySelectorAll: () => fields,
        addEventListener() {},
    };
    const button = {
        dataset: { profile: JSON.stringify({ id: 'tent_WC-001', fullName: 'Maria', householdSize: 8, fourPsBeneficiary: true }) },
        addEventListener(event, callback) { this[event] = callback; },
    };
    const modal = { classList: { add() {}, remove() {} }, addEventListener() {} };
    const other = { addEventListener() {} };
    const document = {
        getElementById(id) {
            if (id === 'editFamilyProfileForm') return form;
            if (id === 'editFamilyProfileModal') return modal;
            return other;
        },
        querySelectorAll() { return [button]; },
    };
    const context = {
        document,
        createFamilyMemberRows: () => ({ reset() {}, hasHiddenData: () => false }),
    };
    vm.runInNewContext(script, context);
    button.click();

    assert.equal(form.action, '/features/family-profiles/tent_WC-001');
    assert.equal(fields[0].value, 'csrf-token');
    assert.equal(fields[1].value, 'PUT');
    assert.equal(fields[2].value, 'tent_WC-001');
    assert.equal(fields[3].value, '0');
    assert.equal(fields[4].checked, true);
    assert.equal(fields[5].value, 'Maria');
    assert.equal(fields[6].value, 8);
});
