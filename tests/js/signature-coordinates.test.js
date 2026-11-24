const test = require('node:test');
const assert = require('node:assert/strict');
const { placementArea, fitsPlacement, pageIndex, fileProblem, documentFingerprint } = require('../../resources/js/signature-coordinates');

test('placement leaves space for the signature and the offset DSS checks', () => {
    const area = placementArea([0, 7.8299813, 595.5, 850.07996]);
    assert.deepEqual(area, { left: 12, bottom: 20, right: 583, top: 830 });
    assert.equal(fitsPlacement(3, 790, area), false);
    assert.equal(fitsPlacement(12, 790, area), false);
    assert.equal(fitsPlacement(12, 758, area), true);
    assert.equal(fitsPlacement(12, 759, area), false);
    assert.equal(fitsPlacement(403, 20, area), true);
    assert.equal(fitsPlacement(404, 20, area), false);
    assert.equal(fitsPlacement(12, 19, area), false);
});

test('fractional origins are rounded inwards and small pages reject manual placement', () => {
    const area = placementArea([20.25, 30.25, 400.75, 600.75]);
    assert.deepEqual(area, { left: 33, bottom: 43, right: 388, top: 558 });
    assert.equal(fitsPlacement(33, 43, area), true);
    assert.equal(fitsPlacement(32, 43, area), false);
    assert.equal(fitsPlacement(33.5, 43, area), false);
    const smallPage = placementArea([0.5, 0.5, 90.5, 40.5]);
    assert.equal(fitsPlacement(smallPage.left, smallPage.bottom, smallPage), false);
});

test('human page numbers map to valid zero-based API indexes', () => {
    assert.equal(pageIndex(1, 3), 0);
    assert.equal(pageIndex(3, 3), 2);
    assert.equal(pageIndex(100, 3), 2);
    assert.equal(pageIndex(-1, 3), 0);
});
test('PDF signing cannot silently discard extra files or accept a non-PDF', () => {
    const pdf = { name: 'document.pdf', type: 'application/pdf' };
    assert.equal(fileProblem([pdf], 'pdf'), '');
    assert.match(fileProblem([pdf, pdf], 'pdf'), /one file/);
    assert.match(fileProblem([{ name: 'photo.jpg', type: 'image/jpeg' }], 'pdf'), /Choose a PDF/);
    assert.equal(fileProblem([{ name: 'photo.jpg' }, pdf], 'asice'), '');
    assert.match(fileProblem([{ name: 'document.pdf' }], 'enveloped-xades'), /XML/);
});

// File identity is based on contents, not a reused filename or file size.
test('validation reselection recognizes the same PDF and rejects different contents', async () => {
    const { webcrypto, createHash } = require('node:crypto');
    const original = Buffer.from('%PDF original document');
    const changed = Buffer.from('%PDF different content');
    const file = data => ({ arrayBuffer: async () => data });
    const fingerprint = createHash('sha256').update(original).digest('hex');
    assert.equal(await documentFingerprint(file(original), webcrypto), fingerprint);
    assert.notEqual(await documentFingerprint(file(changed), webcrypto), fingerprint);
    assert.equal(await documentFingerprint(null, webcrypto), '');
    assert.equal(await documentFingerprint(file(original), {}), '');
    assert.equal(pageIndex(9, 2), 1);
});
