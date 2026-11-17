function fieldLabel(key) {
    return key.replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[_-]+/g, ' ')
        .replace(/^./, letter => letter.toUpperCase());
}

function flatten(value, keys = [], labels = []) {
    if (value !== null && typeof value === 'object') {
        const entries = Object.entries(value);
        if (entries.length) {
            return entries.flatMap(([key, child]) => flatten(child, [...keys, key], [
                ...labels, Array.isArray(value) ? String(Number(key) + 1) : fieldLabel(key),
            ]));
        }
    }

    return [{
        key: JSON.stringify(keys),
        label: labels.join(' / '),
        value: value === null || value === '' || typeof value === 'object'
            ? '-' : typeof value === 'boolean' ? (value ? 'Yes' : 'No') : String(value),
    }];
}

function tableData(profiles) {
    const columns = new Map();
    const values = profiles.map(profile => {
        const fields = Object.entries(profile)
            .filter(([key]) => key !== 'fullName')
            .flatMap(([key, value]) => flatten(value, [key], [fieldLabel(key)]));
        fields.forEach(field => columns.set(field.key, field.label));
        return new Map(fields.map(field => [field.key, field.value]));
    });
    const keys = [...columns.keys()];
    return {
        head: ['Family head', ...columns.values()],
        body: profiles.map((profile, index) => [
            String(profile.fullName || profile.headOfFamily || profile.familyName || 'Unnamed'),
            ...keys.map(key => values[index].get(key) ?? '-'),
        ]),
    };
}

export function setupFamilySummary(elements, url) {
    const { status, head, body, count, asOf } = elements;
    let currentRefresh = null;
    let pending = false;

    function render(data) {
        const table = tableData(data.profiles);
        const header = document.createElement('tr');
        table.head.forEach(label => {
            const cell = document.createElement('th');
            cell.scope = 'col';
            cell.textContent = label;
            header.appendChild(cell);
        });
        const rows = document.createDocumentFragment();
        table.body.forEach(values => {
            const row = document.createElement('tr');
            values.forEach((value, index) => {
                const cell = document.createElement(index === 0 ? 'th' : 'td');
                if (index === 0) cell.scope = 'row';
                cell.textContent = value;
                row.appendChild(cell);
            });
            rows.appendChild(row);
        });
        head.replaceChildren(header);
        body.replaceChildren(rows);
        count.textContent = `Families: ${data.profiles.length}`;
        asOf.textContent = 'As of: ' + new Intl.DateTimeFormat('en-PH', {
            dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Asia/Manila',
        }).format(new Date(data.asOf));
        status.textContent = data.profiles.length ? '' : 'No family profiles have been added yet.';
        status.hidden = data.profiles.length > 0;
    }

    async function fetchReport() {
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Unable to load family profiles.');
            }
            const data = await response.json();
            if (!Array.isArray(data.profiles) || !data.profiles.every(profile => profile && typeof profile === 'object' && !Array.isArray(profile))
                || !data.asOf || Number.isNaN(Date.parse(data.asOf))) {
                throw new Error('Invalid family profile summary.');
            }
            render(data);
        } catch (error) {
            console.error('Unable to load family profile summary:', error);
            status.textContent = 'Unable to load the family profile summary. Displayed data may be out of date; retrying automatically.';
            status.hidden = false;
        }
    }

    function refresh() {
        if (currentRefresh) {
            pending = true;
            return currentRefresh;
        }
        currentRefresh = (async () => {
            do {
                pending = false;
                await fetchReport();
            } while (pending);
        })().finally(() => { currentRefresh = null; });
        return currentRefresh;
    }

    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    refresh();
    setInterval(() => { if (!document.hidden) refresh(); }, 15000);
}
