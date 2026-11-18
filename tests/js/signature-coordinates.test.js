const test = require('node:test');
const assert = require('node:assert/strict');
const { fitPlacement, pageIndex, fileProblem } = require('../../resources/js/signature-coordinates');

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
