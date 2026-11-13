import assert from 'node:assert/strict';
import test from 'node:test';

test('CMEC defaults to 50 scan-ready tent codes and supports saved counts', async () => {
    const originalFetch = globalThis.fetch;
    globalThis.fetch = async () => ({ ok: true, json: async () => ({}) });
    try {
        const { barangayList, applyTentCounts, tentCountsReady } = await import('../../public/js/barangayList.js');
        await tentCountsReady;
        const cmec = barangayList.find(site => site.code === 'CMEC');
        const cdj = barangayList.find(site => site.code === 'CDJ');
        assert.equal(cmec.name, 'The City-managed Evacuation Center');
        assert.equal(Object.keys(cmec.tents).length, 50);
        assert.equal(Object.keys(cmec.tents)[0], 'CMEC-001');
        assert.equal(Object.keys(cmec.tents)[49], 'CMEC-050');
        assert.equal(Object.keys(cdj.tents).length, 10);
        applyTentCounts({ CMEC: 52 });
        assert.equal(Object.keys(cmec.tents).length, 52);
        assert.ok(cmec.tents['CMEC-052']);
        assert.equal(Object.keys(cdj.tents).length, 10);
    } finally {
        globalThis.fetch = originalFetch;
    }
});
