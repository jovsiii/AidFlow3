export function getCmecDetails(center, occupiedTents) {
    const occupiedById = new Map(occupiedTents.map(tent => [tent.tentId, tent]));
    const tents = Object.keys(center.tents).map(tentId => ({
        tentId,
        occupied: occupiedById.has(tentId),
        date: occupiedById.get(tentId)?.date || '--',
    }));
    const occupied = tents.filter(tent => tent.occupied).length;
    return {
        tents,
        occupied,
        total: tents.length,
        available: tents.length - occupied,
        percent: tents.length ? (occupied / tents.length * 100).toFixed(2) : '0.00',
    };
}

export function createCmecDetails(center, occupiedTents) {
    const modal = document.getElementById('cmec-details-modal');
    const closeButton = document.getElementById('close-cmec-details');
    let previousFocus;
    const close = () => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        previousFocus?.focus();
    };
    closeButton.addEventListener('click', close);
    modal.addEventListener('click', event => {
        if (event.target === modal) close();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !modal.classList.contains('hidden')) close();
    });

    return () => {
        const details = getCmecDetails(center, occupiedTents);
        document.getElementById('cmec-total-tents').textContent = details.total;
        document.getElementById('cmec-occupied-tents').textContent = details.occupied;
        document.getElementById('cmec-available-tents').textContent = details.available;
        document.getElementById('cmec-occupancy-percent').textContent = `${details.percent}%`;
        const circumference = 2 * Math.PI * 90;
        const circle = document.getElementById('cmec-occupancy-circle');
        circle.style.strokeDasharray = circumference;
        circle.style.strokeDashoffset = circumference * (1 - Number(details.percent) / 100);
        const table = document.getElementById('cmec-tent-table');
        table.replaceChildren();
        details.tents.forEach(tent => {
            const row = document.createElement('tr');
            [tent.date, tent.tentId, tent.occupied ? 'Occupied' : 'Available'].forEach((value, index) => {
                const cell = document.createElement('td');
                cell.textContent = value;
                if (index === 2) cell.className = tent.occupied ? 'text-red-500' : 'text-green-500';
                row.appendChild(cell);
            });
            table.appendChild(row);
        });
        previousFocus = document.activeElement;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        closeButton.focus();
    };
}
