export function addEvacuationCenterMarker(map, feature, center, openDetails) {
    if (feature.properties?.name !== 'Corazon de Jesus' || feature.geometry?.type !== 'Polygon') {
        return;
    }

    const capacity = Object.keys(center.tents).length;
    const description = `CMEC evacuation center - capacity: ${capacity} tents (approximate location)`;
    const marker = L.marker([14.6069, 121.0292], {
        icon: L.icon({
            iconUrl: '/assets/CSWD.png',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            tooltipAnchor: [10, 0],
        }),
        title: description,
        alt: description,
    });

    marker.bindTooltip('CMEC', { direction: 'right' });
    marker.on('click', openDetails);
    marker.addTo(map);
}
