export function addEvacuationCenterMarker(map, feature) {
    if (feature.properties?.name !== 'Corazon de Jesus' || feature.geometry?.type !== 'Polygon') {
        return;
    }

    const marker = L.marker([14.6069, 121.0292], {
        icon: L.icon({
            iconUrl: '/assets/CSWD.png',
            iconSize: [20, 20],
            iconAnchor: [10, 10],
            tooltipAnchor: [10, 0],
        }),
        title: 'CMEC evacuation center - capacity: 50 tents (approximate location)',
        alt: 'CMEC evacuation center - capacity: 50 tents (approximate location)',
    });

    marker.bindTooltip('CMEC', { permanent: true, direction: 'right' });
    marker.bindPopup('CMEC evacuation center<br>Capacity: 50 tents<br>Approximate location within CDJ.');
    marker.addTo(map);
}
