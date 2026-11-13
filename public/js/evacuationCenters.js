export function addEvacuationCenterMarker(map, feature, layer) {
    if (feature.properties?.name !== 'Corazon de Jesus' || feature.geometry?.type !== 'Polygon') {
        return;
    }

    const marker = L.marker(layer.getBounds().getCenter(), {
        icon: L.icon({
            iconUrl: '/assets/CSWD.png',
            iconSize: [20, 20],
            iconAnchor: [10, 29],
            tooltipAnchor: [10, -19],
        }),
        title: 'CMEC evacuation center (approximate location)',
        alt: 'CMEC evacuation center (approximate location)',
    });

    marker.bindTooltip('CMEC', { permanent: true, direction: 'right' });
    marker.bindPopup('CMEC evacuation center<br>Approximate location within CDJ.');
    marker.addTo(map);
}
