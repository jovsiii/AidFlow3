function fieldLabel(key) {
    return key.replace(/([a-z0-9])([A-Z])/g, '$1 $2')
        .replace(/[_-]+/g, ' ')
        .replace(/^./, letter => letter.toUpperCase());
}

function profileRows(value, path = '') {
    if (value !== null && typeof value === 'object') {
        const entries = Object.entries(value);
        if (entries.length === 0) return [[path, '-']];
        return entries.flatMap(([key, child]) => {
            const label = Array.isArray(value) ? String(Number(key) + 1) : fieldLabel(key);
            return profileRows(child, path ? `${path} / ${label}` : label);
        });
    }

    return [[path, value === null || value === '' ? '-' : typeof value === 'boolean' ? (value ? 'Yes' : 'No') : String(value)]];
}

export function setupFamilySummaryExport(button, status, url) {
    button.addEventListener('click', async () => {
        button.disabled = true;
        status.hidden = true;
        try {
            if (!window.jspdf?.jsPDF || !window.jspdf.jsPDF.API.autoTable) {
                throw new Error('PDF tools could not be loaded.');
            }
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok || !response.headers.get('content-type')?.includes('application/json')) {
                throw new Error('Unable to load family profiles.');
            }
            const report = await response.json();
            if (!Array.isArray(report.profiles) || !report.asOf || Number.isNaN(Date.parse(report.asOf))) {
                throw new Error('Invalid family profile summary.');
            }
            const asOf = new Intl.DateTimeFormat('en-PH', {
                dateStyle: 'medium', timeStyle: 'medium', timeZone: 'Asia/Manila',
            }).format(new Date(report.asOf));
            const body = report.profiles.flatMap((profile, index) => [
                [{ content: `Family ${index + 1}: ${profile.fullName || profile.headOfFamily || profile.familyName || 'Unnamed'}`,
                    colSpan: 2, styles: { fillColor: [253, 247, 247], textColor: [150, 5, 5], fontStyle: 'bold' } }],
                ...profileRows(profile),
            ]);
            const pdf = new window.jspdf.jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });
            pdf.autoTable({
                startY: 32,
                margin: { top: 32, bottom: 15 },
                head: [['Field', 'Details']],
                body: body.length ? body : [[{ content: 'No family profiles have been added yet.', colSpan: 2 }]],
                theme: 'grid',
                headStyles: { fillColor: [150, 5, 5] },
                columnStyles: { 0: { cellWidth: 90 } },
                didDrawPage: () => {
                    pdf.setTextColor(17, 24, 39);
                    pdf.setFont('helvetica', 'bold');
                    pdf.setFontSize(15);
                    pdf.text('Family Profile Summary', 14, 14);
                    pdf.setFont('helvetica', 'normal');
                    pdf.setFontSize(9);
                    pdf.text(`As of: ${asOf} | Total families: ${report.profiles.length}`, 14, 22);
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
            button.disabled = false;
        }
    });
}
