const test = require('node:test');
const assert = require('node:assert/strict');
const vm = require('node:vm');
const fs = require('node:fs');
const { File } = require('node:buffer');
const { webcrypto, createHash } = require('node:crypto');
const coordinates = require('../../resources/js/signature-coordinates');

function fixture(contents, beforePageLoad = () => {}, sampleBlob = async () => new Blob([contents])) {
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
    let selectedFormat = 'pdf';
    const options = ['pdf', 'asice', 'enveloped-xades'].map(value => {
        const option = node();
        option.value = value;
        option.click = () => {
            selectedFormat = value;
            option.handlers.change();
        };
        return option;
    });
    for (const option of options) {
        Object.defineProperty(option, 'checked', { set(checked) { if (checked) selectedFormat = option.value; } });
    }
    const optionFor = value => options.find(option => option.value === value);
    get('unsigned_file').dispatchEvent = event => get('unsigned_file').handlers[event.type]();
    const automatic = get('automatic');
    automatic.dataset.preset = 'auto';
    form.querySelectorAll = selector => {
        if (selector === 'input[name="containerType"]') return options;
        if (selector === '[data-preset]') return [automatic];
        return [];
    };
    form.querySelector = selector => {
        if (selector.includes(':checked + span')) return { textContent: selectedFormat };
        if (selector.includes(':checked')) return optionFor(selectedFormat);
        const match = selector.match(/\[value="([^"]+)"\]/);
        return match ? optionFor(match[1]) : get(selector);
    };
    get('signType').options = [{ text: 'Hosted signing' }];
    get('signType').selectedIndex = 0;
    get('pdf_x').value = '900'; get('pdf_y').value = '900';
    get('pdf_page').value = '9';
    get('placement-document-fingerprint').value = createHash('sha256').update(contents).digest('hex');
    get('preview-stage').clientWidth = 700;
    const loadedPages = [];
    const pdf = { numPages: 2, destroy() {}, getPage: async number => {
        loadedPages.push(number);
        await beforePageLoad(number);
        return { view: [0, 0, 595, 842], getViewport: () => ({ width: 595, height: 842,
            convertToViewportRectangle: rectangle => rectangle }),
            render: () => ({ promise: Promise.resolve(), cancel() {} }) };
    } };
    const source = fs.readFileSync(require.resolve('../../resources/js/signing-playground'), 'utf8')
        .replace(/await import\([^;]+\);/, 'await Promise.resolve(pdfjsStub);');
    vm.runInNewContext(source, { require: () => ({ ...coordinates, documentFingerprint: file => coordinates.documentFingerprint(file, webcrypto) }),
        document: { getElementById: id => id === 'validation-errors' ? null : get(id), createElement: node },
        window: { innerWidth: 1200, addEventListener() {} },
        fetch: async () => ({ ok: true, blob: sampleBlob }),
        File,
        Event,
        DataTransfer: class {
            files = [];
            items = { add: file => this.files.push(file) };
        },
        pdfjsStub: { GlobalWorkerOptions: {}, getDocument: () => ({ promise: Promise.resolve(pdf), destroy() {} }) },
    });
    async function select(data) {
        get('unsigned_file').files = [{ name: 'same-name.pdf', type: 'application/pdf', size: data.length,
            arrayBuffer: async () => data }];
        await get('unsigned_file').handlers.change();
    }
    return { get, select, loadedPages, optionFor };
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

test('format suggestion switches incompatible selections without replacing the files', async () => {
    const app = fixture(Buffer.from('%PDF test'));
    const input = app.get('unsigned_file');
    const suggestion = app.get('suggested-format');

    app.optionFor('enveloped-xades').click();
    await app.select(Buffer.from('%PDF test'));
    assert.equal(suggestion.textContent, 'Switch to PDF');
    suggestion.handlers.click();
    assert.equal(suggestion.hidden, true);
    assert.equal(input.files[0].name, 'same-name.pdf');

    const files = [...input.files, { name: 'notes.txt', size: 12 }];
    input.files = files;
    await input.handlers.change();
    assert.equal(suggestion.textContent, 'Switch to ASiC-E');
    suggestion.handlers.click();
    assert.equal(app.get('file-feedback').hidden, true);
    assert.equal(input.files, files);
});

test('a PDF selected under ASiC-E retains its fingerprint after switching to PDF', async () => {
    const original = Buffer.from('%PDF original document');
    const app = fixture(original);
    app.optionFor('asice').click();
    await app.select(original);
    assert.equal(app.get('placement-document-fingerprint').value, createHash('sha256').update(original).digest('hex'));
    app.optionFor('pdf').click();
    assert.equal(app.get('start-signing').disabled, true);
});

test('submission waits for the new page and its placement bounds', async () => {
    const original = Buffer.from('%PDF original document');
    let finishPage;
    const pageReady = new Promise(resolve => { finishPage = resolve; });
    const app = fixture(original, number => number === 2 ? pageReady : undefined);
    app.get('pdf_page').value = '1';
    await app.select(original);

    const changing = app.get('next-page').handlers.click();
    assert.equal(app.get('start-signing').disabled, true);
    assert.equal(app.get('pdf_page_index').value, 0);
    let prevented = false;
    app.get('signing-playground').handlers.submit({ preventDefault() { prevented = true; } });
    assert.equal(prevented, true);

    finishPage();
    await changing;
    assert.equal(app.get('pdf_page_index').value, 1);
    assert.equal(app.get('start-signing').disabled, false);
});

test('sample PDF selection replaces the XML-only file picker filter', async () => {
    const app = fixture(Buffer.from('%PDF sample'));
    app.optionFor('enveloped-xades').click();
    assert.equal(app.get('unsigned_file').accept, '.xml,text/xml,application/xml');

    const sampleButton = app.get('use-sample-file');
    await sampleButton.handlers.click({ currentTarget: sampleButton });
    assert.equal(app.get('unsigned_file').files[0].name, 'sample-agreement.pdf');
    assert.equal(app.get('unsigned_file').accept, '.pdf,application/pdf');
});

test('a late sample download keeps the newer file and output format', async () => {
    let completeDownload;
    const blob = new Promise(resolve => { completeDownload = resolve; });
    const original = Buffer.from('%PDF original');
    const app = fixture(original, undefined, () => blob);
    const sampleButton = app.get('use-sample-file');
    const downloading = sampleButton.handlers.click({ currentTarget: sampleButton });
    app.optionFor('asice').click();
    await app.select(original);
    completeDownload(new Blob(['%PDF sample']));
    await downloading;
    assert.equal(app.get('unsigned_file').files[0].name, 'same-name.pdf');
    assert.equal(app.get('unsigned_file').accept, '');
    assert.equal(sampleButton.disabled, false);
});

test('changing only the output format cancels a pending sample download', async () => {
    let completeDownload;
    const blob = new Promise(resolve => { completeDownload = resolve; });
    const app = fixture(Buffer.from('%PDF original'), undefined, () => blob);
    const sampleButton = app.get('use-sample-file');
    const downloading = sampleButton.handlers.click({ currentTarget: sampleButton });
    app.optionFor('enveloped-xades').click();
    completeDownload(new Blob(['%PDF sample']));
    await downloading;
    assert.equal(app.get('unsigned_file').files.length, 0);
    assert.equal(app.get('unsigned_file').accept, '.xml,text/xml,application/xml');
});

test('PDF digest signing keeps the preview and disables appearance controls', async () => {
    const original = Buffer.from('%PDF original');
    const app = fixture(original);
    await app.select(original);
    const flow = app.get('signType');
    flow.value = 'digest';
    flow.handlers.change();
    assert.equal(app.get('pdf-page').hidden, false);
    assert.equal(app.get('appearance-controls').disabled, true);
    assert.equal(app.get('signature-marker').hidden, true);
    assert.equal(app.get('digest-appearance-note').hidden, false);

    flow.value = 'external';
    flow.handlers.change();
    assert.equal(app.get('appearance-controls').disabled, false);
    assert.equal(app.get('signature-marker').hidden, false);
    assert.equal(app.get('digest-appearance-note').hidden, true);
});

test('automatic placement preserves a manually selected page without a preview', async () => {
    const app = fixture(Buffer.from('%PDF test'));
    const page = app.get('pdf_page');
    page.value = '3';
    await page.handlers.change();
    app.get('automatic').handlers.click();
    assert.equal(app.get('pdf_x').value, '');
    assert.equal(app.get('pdf_y').value, '');
    assert.equal(page.value, '3');
    assert.equal(app.get('pdf_page_index').value, 2);
});
