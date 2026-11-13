import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';
import { addEvacuationCenterMarker } from '../../public/js/evacuationCenters.js';

test('CMEC is marked once at an approximate position inside CDJ', async () => {
    const geojson = JSON.parse(await readFile(new URL('../../public/geojson/sanjuan.geojson', import.meta.url)));
    const feature = geojson.features.find(item => item.properties?.name === 'Corazon de Jesus');
    const ring = feature.geometry.coordinates[0];
    const position = { lat: 14.6069, lng: 121.0292 };
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const [xi, yi] = ring[i];
        const [xj, yj] = ring[j];
        if ((yi > position.lat) !== (yj > position.lat)
            && position.lng < (xj - xi) * (position.lat - yi) / (yj - yi) + xi) {
            inside = !inside;
        }
    }
    assert.ok(inside);

    const calls = [];
    const marker = {
        bindTooltip: (...args) => calls.push(['tooltip', ...args]),
        on: (...args) => calls.push(['on', ...args]),
        addTo: (...args) => calls.push(['addTo', ...args]),
    };
    globalThis.L = {
        icon: options => options,
        marker: (...args) => {
            calls.push(['marker', ...args]);
            return marker;
        },
    };
    try {
        const map = {};
        addEvacuationCenterMarker(map, { properties: { name: 'Other' } });
        assert.equal(calls.length, 0);
        const center = { tents: Object.fromEntries(Array.from({ length: 50 }, (_, i) => [`CMEC-${i + 1}`, ''])) };
        const openDetails = () => {};
        addEvacuationCenterMarker(map, feature, center, openDetails);
        assert.equal(calls.length, 4);
        assert.deepEqual(calls[0][1], [position.lat, position.lng]);
        assert.equal(calls[0][2].icon.iconUrl, '/assets/CSWD.png');
        assert.deepEqual(calls[0][2].icon.iconSize, [26, 26]);
        assert.deepEqual(calls[0][2].icon.iconAnchor, [13, 13]);
        assert.deepEqual(calls[0][2].icon.tooltipAnchor, [13, 0]);
        assert.equal(calls[1][1], 'CMEC');
        assert.notEqual(calls[1][2].permanent, true);
        assert.equal(calls[2][1], 'click');
        assert.equal(calls[2][2], openDetails);
        assert.match(calls[0][2].title, /capacity: 50 tents/);
        assert.match(calls[0][2].title, /approximate location/);
        assert.equal(calls[3][1], map);

        calls.length = 0;
        center.tents['CMEC-51'] = '';
        addEvacuationCenterMarker(map, feature, center, openDetails);
        assert.match(calls[0][2].title, /capacity: 51 tents/);
    } finally {
        delete globalThis.L;
    }
});
