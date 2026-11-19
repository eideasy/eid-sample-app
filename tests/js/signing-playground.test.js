const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const { webcrypto, createHash } = require('node:crypto');
const coordinates = require('../../resources/js/signature-coordinates');

function fixture(contents) {
    const nodes = new Map();
    function node() {
        return { value: '', files: [], style: {}, dataset: {}, firstChild: {}, checked: false,
            classList: { toggle() {}, add() {}, remove() {} }, handlers: {},
            addEventListener(name, handler) { this.handlers[name] = handler; },
            setAttribute() {}, removeAttribute() {}, replaceChildren() {}, append() {}, focus() {},
            getContext() { return {}; },
        };
    }
    const get = id => { if (!nodes.has(id)) nodes.set(id, node()); return nodes.get(id); };
    const form = get('signing-playground');
    form.querySelectorAll = () => [];
    form.querySelector = selector => selector.includes(':checked + span') ? { textContent: 'PDF' }
        : selector.includes(':checked') ? { value: 'pdf' } : get(selector);
    get('signType').options = [{ text: 'Hosted signing' }];
    get('signType').selectedIndex = 0;
    get('pdf_x').value = '900'; get('pdf_y').value = '900';
    get('pdf_page').value = '9';
    get('placement-document-fingerprint').value = createHash('sha256').update(contents).digest('hex');
    get('preview-stage').clientWidth = 700;
    const loadedPages = [];
    const pdf = { numPages: 2, destroy() {}, getPage: async number => {
        loadedPages.push(number);
        return { view: [0, 0, 595, 842], getViewport: () => ({ width: 595, height: 842,
            convertToViewportRectangle: rectangle => rectangle }),
            render: () => ({ promise: Promise.resolve(), cancel() {} }) };
    } };
    const source = fs.readFileSync(require.resolve('../../resources/js/signing-playground'), 'utf8')
        .replace(/await import\([^;]+\);/, 'await Promise.resolve(pdfjsStub);');
    vm.runInNewContext(source, { require: () => ({ ...coordinates, documentFingerprint: file => coordinates.documentFingerprint(file, webcrypto) }),
        document: { getElementById: id => id === 'validation-errors' ? null : get(id), createElement: node },
        window: { innerWidth: 1200, addEventListener() {} },
        pdfjsStub: { GlobalWorkerOptions: {}, getDocument: () => ({ promise: Promise.resolve(pdf), destroy() {} }) },
    });
    async function select(data) {
        get('unsigned_file').files = [{ name: 'same-name.pdf', type: 'application/pdf', size: data.length,
            arrayBuffer: async () => data }];
        await get('unsigned_file').handlers.change();
    }
    return { get, select, loadedPages };
}

test('same upload after validation restores and bounds-checks coordinates and page', async () => {
    const original = Buffer.from('%PDF original document');
    const app = fixture(original);
    const selecting = app.select(original);
    assert.equal(app.get('start-signing').disabled, true);
    let prevented = false;
    app.get('signing-playground').handlers.submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);
    await selecting;
    assert.equal(app.get('pdf_x').value, 415);
    assert.equal(app.get('pdf_y').value, 782);
    assert.equal(app.get('pdf_page').value, 2);
    assert.equal(app.get('pdf_page_index').value, 1);
    assert.deepEqual(app.loadedPages, [2]);
    assert.equal(app.get('start-signing').disabled, false);
});

test('different PDF with the same name resets restored placement', async () => {
    const app = fixture(Buffer.from('%PDF original document'));
    await app.select(Buffer.from('%PDF different content'));
    assert.equal(app.get('pdf_x').value, '');
    assert.equal(app.get('pdf_y').value, '');
    assert.equal(app.get('pdf_page').value, 1);
    assert.equal(app.get('pdf_page_index').value, 0);
    assert.deepEqual(app.loadedPages, [1]);
});

test('shipped playground bundle initializes without module-format errors', () => {
    const bundle = fs.readFileSync(require.resolve('../../public/js/signing-playground.js'), 'utf8');
    assert.doesNotThrow(() => vm.runInNewContext(bundle, { document: { getElementById: () => null } }));
});
