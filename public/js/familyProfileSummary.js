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
    const { button, status, head, body, count, asOf } = elements;
    let report;
    let currentRefresh = null;
    let pending = false;
    let valid = false;
    let exporting = false;

    function updateButton() {
        button.disabled = !valid || exporting;
    }

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
        return table;
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
            report = { ...data, table: render(data), asOfText: asOf.textContent };
            valid = true;
            updateButton();
            return true;
        } catch (error) {
            console.error('Unable to load family profile summary:', error);
            status.textContent = 'Unable to load the family profile summary. Displayed data may be out of date; retrying automatically.';
            status.hidden = false;
            valid = false;
            updateButton();
            return false;
        }
    }

    function refresh() {
        if (currentRefresh) {
            pending = true;
            return currentRefresh;
        }
        currentRefresh = (async () => {
            let success;
            do {
                pending = false;
                success = await fetchReport();
            } while (pending);
            return success;
        })().finally(() => { currentRefresh = null; });
        return currentRefresh;
    }

    button.addEventListener('click', async () => {
        exporting = true;
        updateButton();
        try {
            if (!await refresh()) return;
            if (!window.jspdf?.jsPDF || !window.jspdf.jsPDF.API.autoTable) {
                throw new Error('PDF tools could not be loaded.');
            }
            const pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            pdf.autoTable({
                startY: 32,
                margin: { top: 32, bottom: 15 },
                head: [report.table.head],
                body: report.table.body.length ? report.table.body : [['No family profiles have been added yet.']],
                theme: 'grid',
                styles: { cellWidth: 45, fontSize: 8, overflow: 'linebreak' },
                headStyles: { fillColor: [150, 5, 5] },
                columnStyles: { 0: { cellWidth: 50 } },
                horizontalPageBreak: true,
                horizontalPageBreakRepeat: 0,
                didDrawPage: () => {
                    pdf.setTextColor(17, 24, 39);
                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(15);
                    pdf.text('Family Profile Summary', 14, 14);
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(9);
                    pdf.text(`${report.asOfText} | Total families: ${report.profiles.length}`, 14, 22);
                    pdf.text(`Page ${pdf.internal.getNumberOfPages()}`, pdf.internal.pageSize.getWidth() - 14,
                        pdf.internal.pageSize.getHeight() - 7, { align: 'right' });
                },
            });
            pdf.save('family-profile-summary.pdf');
        } catch (error) {
            console.error('Unable to export family profile summary:', error);
            status.textContent = 'Unable to export the family profile summary. Please check your connection and try again.';
            status.hidden = false;
        } finally {
            exporting = false;
            updateButton();
        }
    });

    document.addEventListener('visibilitychange', () => { if (!document.hidden) refresh(); });
    refresh();
    setInterval(() => { if (!document.hidden) refresh(); }, 15000);
}
