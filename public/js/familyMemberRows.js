export function createFamilyMemberRows(body, template, householdSize) {
    function countFrom(value) {
        const count = Number(value);
        return Number.isInteger(count) && count >= 1 && count <= 100 ? count : 0;
    }

    function addRow(index) {
        const row = template.content.firstElementChild.cloneNode(true);
        row.querySelector('[data-member-number]').textContent = index + 1;
        row.querySelectorAll('[name]').forEach(field => {
            field.name = field.name.replace('__INDEX__', String(index));
            field.setAttribute('aria-label', field.getAttribute('aria-label').replace('__NUMBER__', String(index + 1)));
        });
        body.appendChild(row);
    }

    function sync() {
        const count = countFrom(householdSize.value);
        while (body.children.length < count) addRow(body.children.length);
        Array.from(body.children).forEach((row, index) => {
            row.hidden = index >= count;
            row.querySelectorAll('[name]').forEach(field => {
                field.disabled = row.hidden;
            });
        });
    }

    householdSize.addEventListener('input', sync);
    householdSize.addEventListener('change', sync);

    return {
        reset(size, members) {
            body.replaceChildren();
            const indices = Object.keys(members ?? {})
                .filter(key => /^(0|[1-9]\d*)$/.test(key))
                .map(Number)
                .filter(index => index < 100);
            const existingCount = indices.length ? Math.max(...indices) + 1 : 0;
            const count = Math.max(countFrom(size), existingCount);
            for (let index = 0; index < count; index++) addRow(index);
            householdSize.value = size ?? '';
            sync();
        },
        hasHiddenData() {
            return Array.from(body.children).some(row => row.hidden
                && Array.from(row.querySelectorAll('[name]')).some(field => field.value.trim() !== ''));
        },
    };
}
