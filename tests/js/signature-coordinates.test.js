const test = require('node:test');
const assert = require('node:assert/strict');
const { fitPlacement, pageIndex, fileProblem, documentFingerprint } = require('../../resources/js/signature-coordinates');

test('placement stays inside the actual page dimensions, including a cropped page', () => {
    assert.deepEqual(fitPlacement(590, -10, [0, 0, 595, 842]), { x: 415, y: 0, width: 180, height: 60 });
    assert.deepEqual(fitPlacement(0, 0, [20, 30, 400, 600]), { x: 20, y: 30, width: 180, height: 60 });
    assert.deepEqual(fitPlacement(100, 100, [0, 0, 90, 40]), { x: 0, y: 0, width: 90, height: 40 });
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
    assert.match(fileProblem([pdf, pdf], 'pdf'), /one PDF/);
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
    // Restored page and coordinates are clamped when the matching PDF is previewed.
    assert.equal(pageIndex(9, 2), 1);
    assert.deepEqual(fitPlacement(900, 900, [0, 0, 595, 842]), { x: 415, y: 782, width: 180, height: 60 });
});
