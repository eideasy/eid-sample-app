const { clamp, fitPlacement, pageIndex, fileProblem, documentFingerprint } = require('./signature-coordinates');

(function () {
    const form = document.getElementById('signing-playground');
    if (!form) return;
    const el = id => document.getElementById(id);
    const input = el('unsigned_file');
    const xInput = el('pdf_x');
    const yInput = el('pdf_y');
    const pageInput = el('pdf_page');
    const pageIndexInput = el('pdf_page_index');
    const stage = el('preview-stage');
    const paper = el('pdf-page');
    const marker = el('signature-marker');
    const flow = el('signType');
    const hiddenVisual = el('hide_pdf_visual');
    let documentPdf = null;
    let page = null;
    let viewport = null;
    let currentPage = 1;
    let renderTask = null;
    let loadingTask = null;
    let generation = 0;
    let renderGeneration = 0;
    let pendingRender = Promise.resolve();
    let pointerOffset = null;
    let dimensions = null;
    let submitting = false;
    let selectingFile = false;
    let selectionGeneration = 0;
    const format = () => form.querySelector('input[name="containerType"]:checked').value;
    const pdfSelected = () => format() === 'pdf';
    const hasCustomPosition = () => xInput.value !== '' && yInput.value !== '';
    const describe = message => { el('preview-message').textContent = message; };

    function showProblem(message) {
        el('file-error').textContent = message;
        el('file-error').hidden = !message;
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function updateSummary() {
        const count = input.files.length;
        const flowName = flow.options[flow.selectedIndex].text;
        const formatName = form.querySelector('input[name="containerType"]:checked + span').textContent;
        el('signing-summary').textContent = count ? `${flowName} · ${formatName} · ${count} ${count === 1 ? 'file' : 'files'}` : 'Choose a file to get started.';
        el('start-signing').firstChild.textContent = flow.value === 'eseal' ? 'Create sandbox e-Seal ' : flow.value === 'multisign' ? 'Set up signers ' : 'Continue to signing ';
    }

    function updateMarker() {
        marker.hidden = !page || !hasCustomPosition() || hiddenVisual.checked || !pdfSelected();
        if (marker.hidden || !viewport) return;
        const place = fitPlacement(Number(xInput.value), Number(yInput.value), page.view);
        const rect = viewport.convertToViewportRectangle([place.x, place.y, place.x + place.width, place.y + place.height]);
        marker.style.left = `${Math.min(rect[0], rect[2])}px`;
        marker.style.top = `${Math.min(rect[1], rect[3])}px`;
        marker.style.width = `${Math.abs(rect[2] - rect[0])}px`;
        marker.style.height = `${Math.abs(rect[3] - rect[1])}px`;
        marker.classList.toggle('compact', Math.abs(rect[2] - rect[0]) < 130);
    }

    function setPosition(x, y) {
        if (!page) return;
        const place = fitPlacement(x, y, page.view);
        xInput.value = place.x;
        yInput.value = place.y;
        pageIndexInput.value = currentPage - 1;
        pageInput.value = currentPage;
        updateMarker();
    }

    function updateAppearance() {
        const pdf = pdfSelected();
        el('appearance-controls').disabled = !pdf;
        el('appearance-controls').hidden = !pdf;
        el('non-pdf-note').hidden = pdf;
        // Disabled controls do not leak stale PDF coordinates into ASiC-E/XML requests.
        [xInput, yInput, pageInput, pageIndexInput].forEach(field => { field.disabled = !pdf || hiddenVisual.checked; });
        el('placement-fields').hidden = hiddenVisual.checked;
        form.querySelectorAll('[data-preset]').forEach(button => { button.disabled = !page && button.dataset.preset !== 'auto'; });
        updateMarker();
    }

    async function renderPage() {
        if (!documentPdf) return;
        const revision = ++renderGeneration;
        const currentDocument = documentPdf;
        // Cancellation must settle before the same canvas can be reused.
        if (renderTask) renderTask.cancel();
        await pendingRender.catch(() => {});
        if (revision !== renderGeneration || currentDocument !== documentPdf) return;
        const loadedPage = await currentDocument.getPage(currentPage);
        if (revision !== renderGeneration || currentDocument !== documentPdf) return;
        page = loadedPage;
        const base = page.getViewport({ scale: 1 });
        const maxWidth = Math.max(100, stage.clientWidth - 48);
        const maxHeight = window.innerWidth > 900 ? 390 : 560;
        const scale = Math.min(maxWidth / base.width, maxHeight / base.height, 1.6);
        viewport = page.getViewport({ scale });
        const canvas = el('pdf-canvas');
        const density = Math.min(window.devicePixelRatio || 1, 2);
        canvas.width = Math.floor(viewport.width * density);
        canvas.height = Math.floor(viewport.height * density);
        canvas.style.width = `${viewport.width}px`;
        canvas.style.height = `${viewport.height}px`;
        paper.style.width = `${viewport.width}px`;
        paper.style.height = `${viewport.height}px`;
        paper.hidden = false;
        el('preview-empty').hidden = true;
        el('page-navigation').hidden = false;
        el('page-status').textContent = `Page ${currentPage} of ${documentPdf.numPages}`;
        el('previous-page').disabled = currentPage === 1;
        el('next-page').disabled = currentPage === documentPdf.numPages;
        pageInput.value = currentPage;
        pageInput.max = documentPdf.numPages;
        pageIndexInput.value = currentPage - 1;
        xInput.max = Math.floor(page.view[2]);
        yInput.max = Math.floor(page.view[3]);
        if (hasCustomPosition()) setPosition(Number(xInput.value), Number(yInput.value));
        updateAppearance();
        renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport, transform: density === 1 ? null : [density, 0, 0, density, 0, 0] });
        pendingRender = renderTask.promise;
        try { await pendingRender; } catch (error) { if (error.name !== 'RenderingCancelledException') throw error; }
    }

    async function loadPreview() {
        const revision = ++generation;
        ++renderGeneration;
        if (renderTask) renderTask.cancel();
        if (loadingTask) loadingTask.destroy();
        if (documentPdf) documentPdf.destroy();
        documentPdf = null;
        page = null;
        viewport = null;
        paper.hidden = true;
        marker.hidden = true;
        el('page-navigation').hidden = true;
        el('preview-empty').hidden = false;
        pageInput.removeAttribute('max');
        xInput.removeAttribute('max');
        yInput.removeAttribute('max');
        updateAppearance();
        const files = Array.from(input.files);
        if (!files.length) { describe('Choose a PDF to preview it and position its visual signature. Other files can be signed in an ASiC-E container.'); return; }
        if (!pdfSelected()) { describe(fileProblem(files, format()) || 'Your files are ready for the selected container format. PDF appearance settings are not used for this format.'); return; }
        if (fileProblem(files, 'pdf')) { describe('Choose a single PDF to open the placement preview.'); return; }
        describe('Opening your PDF locally…');
        try {
            const pdfjs = await import(/* webpackIgnore: true */ '/js/vendor/pdfjs/pdf.js');
            if (revision !== generation) return;
            pdfjs.GlobalWorkerOptions.workerSrc = '/js/vendor/pdfjs/pdf.worker.js';
            const data = new Uint8Array(await files[0].arrayBuffer());
            if (revision !== generation) return;
            const task = pdfjs.getDocument({ data, isEvalSupported: false, cMapUrl: '/js/vendor/pdfjs/cmaps/', cMapPacked: true, standardFontDataUrl: '/js/vendor/pdfjs/standard_fonts/' });
            loadingTask = task;
            task.onPassword = () => { task.destroy(); };
            const loaded = await task.promise;
            if (revision !== generation) { loaded.destroy(); return; }
            documentPdf = loaded;
            currentPage = pageIndex(pageInput.value, documentPdf.numPages) + 1;
            await renderPage();
        } catch (error) {
            if (revision !== generation) return;
            paper.hidden = true;
            page = null;
            el('preview-empty').hidden = false;
            el('page-navigation').hidden = true;
            describe('This PDF could not be previewed. It may be encrypted or unsupported. Choose another file, or use automatic placement or the numeric fields.');
            updateAppearance();
        }
    }

    async function fileChange() {
        const revision = ++selectionGeneration;
        ++generation;
        ++renderGeneration;
        const fingerprintInput = el('placement-document-fingerprint');
        const fingerprint = fingerprintInput.value;
        fingerprintInput.value = '';
        selectingFile = true;
        el('start-signing').disabled = true;
        // Old input includes this local content fingerprint after validation.
        // A different PDF must reset placement even if it reuses the filename.
        const selectedFingerprint = input.files.length === 1 && pdfSelected()
            ? await documentFingerprint(input.files[0]) : '';
        if (revision !== selectionGeneration) return;
        const sameDocument = fingerprint !== '' && fingerprint === selectedFingerprint;
        fingerprintInput.value = selectedFingerprint;
        if (!sameDocument) {
            xInput.value = ''; yInput.value = ''; pageInput.value = 1; pageIndexInput.value = '';
        }
        form.classList.toggle('has-files', input.files.length > 0);
        form.querySelector('.setup-section h2').textContent = input.files.length ? 'Your files.' : 'Start with a file.';
        const list = el('selected-files');
        list.replaceChildren();
        Array.from(input.files).forEach((file, index) => {
            const item = document.createElement('li');
            const text = document.createElement('span');
            text.textContent = file.name;
            const size = document.createElement('small');
            size.textContent = file.size < 1024 * 1024 ? `${Math.ceil(file.size / 1024)} KB` : `${(file.size / 1024 / 1024).toFixed(1)} MB`;
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.textContent = 'Remove';
            remove.setAttribute('aria-label', `Remove ${file.name}`);
            remove.addEventListener('click', () => {
                const transfer = new DataTransfer();
                Array.from(input.files).filter((_file, i) => i !== index).forEach(kept => transfer.items.add(kept));
                input.files = transfer.files;
                fileChange();
            });
            item.append(text, size, remove);
            list.append(item);
        });
        showProblem(input.files.length ? fileProblem(Array.from(input.files), format()) : '');
        updateSummary();
        try { await loadPreview(); }
        finally {
            if (revision === selectionGeneration) {
                selectingFile = false;
                el('start-signing').disabled = false;
            }
        }
    }

    input.addEventListener('change', fileChange);
    el('use-sample-file').addEventListener('click', async event => {
        const button = event.currentTarget;
        button.disabled = true;
        try {
            const response = await fetch('/samples/signing-demo.pdf');
            if (!response.ok) throw new Error('Sample unavailable');
            const transfer = new DataTransfer();
            transfer.items.add(new File([await response.blob()], 'sample-agreement.pdf', { type: 'application/pdf' }));
            input.files = transfer.files;
            form.querySelector('input[name="containerType"][value="pdf"]').checked = true;
            form.querySelectorAll('[data-format]').forEach(node => { node.hidden = node.dataset.format !== 'pdf'; });
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (error) { showProblem('The sample could not be loaded. Please choose a file instead.'); }
        finally { button.disabled = false; }
    });
    flow.addEventListener('change', () => {
        form.querySelectorAll('[data-flow]').forEach(node => { node.hidden = node.dataset.flow !== flow.value; });
        updateSummary();
    });
    form.querySelectorAll('input[name="containerType"]').forEach(radio => radio.addEventListener('change', () => {
        form.querySelectorAll('[data-format]').forEach(node => { node.hidden = node.dataset.format !== format(); });
        input.accept = pdfSelected() ? '.pdf,application/pdf' : format() === 'enveloped-xades' ? '.xml,text/xml,application/xml' : '';
        showProblem(input.files.length ? fileProblem(Array.from(input.files), format()) : '');
        updateSummary();
        loadPreview();
    }));
    hiddenVisual.addEventListener('change', updateAppearance);
    [xInput, yInput].forEach(field => field.addEventListener('input', () => {
        if (page && hasCustomPosition()) setPosition(Number(xInput.value), Number(yInput.value));
        else updateMarker();
    }));
    pageInput.addEventListener('change', async () => {
        if (documentPdf) { currentPage = pageIndex(pageInput.value, documentPdf.numPages) + 1; await renderPage(); }
        else pageIndexInput.value = Math.max(0, Math.trunc(Number(pageInput.value) || 1) - 1);
    });
    el('previous-page').addEventListener('click', async () => { currentPage = Math.max(1, currentPage - 1); await renderPage(); });
    el('next-page').addEventListener('click', async () => { currentPage = Math.min(documentPdf.numPages, currentPage + 1); await renderPage(); });
    form.querySelectorAll('[data-preset]').forEach(button => button.addEventListener('click', () => {
        if (button.dataset.preset === 'auto') { xInput.value = ''; yInput.value = ''; pageIndexInput.value = page ? currentPage - 1 : ''; pageInput.value = currentPage; updateMarker(); return; }
        if (!page) return;
        setPosition(button.dataset.preset === 'left' ? page.view[0] + 24 : page.view[2] - 204, page.view[1] + 24);
    }));

    function pointFromEvent(event) {
        const rect = paper.getBoundingClientRect();
        return viewport.convertToPdfPoint((event.clientX - rect.left) * viewport.width / rect.width, (event.clientY - rect.top) * viewport.height / rect.height);
    }
    paper.addEventListener('pointerdown', event => {
        if (!page || hiddenVisual.checked || event.button !== 0) return;
        const point = pointFromEvent(event);
        const dragging = marker.contains(event.target) && hasCustomPosition();
        pointerOffset = dragging ? [point[0] - Number(xInput.value), point[1] - Number(yInput.value)] : [0, 0];
        paper.setPointerCapture(event.pointerId);
        setPosition(point[0] - pointerOffset[0], point[1] - pointerOffset[1]);
        marker.focus({ preventScroll: true });
        event.preventDefault();
    });
    paper.addEventListener('pointermove', event => {
        if (!pointerOffset || !page) return;
        const point = pointFromEvent(event);
        setPosition(point[0] - pointerOffset[0], point[1] - pointerOffset[1]);
    });
    ['pointerup', 'pointercancel', 'lostpointercapture'].forEach(type => paper.addEventListener(type, () => { pointerOffset = null; }));
    marker.addEventListener('keydown', event => {
        const movement = { ArrowLeft: [-1, 0], ArrowRight: [1, 0], ArrowUp: [0, 1], ArrowDown: [0, -1] }[event.key];
        if (!movement || !page) return;
        event.preventDefault();
        // Convert screen directions through the viewport, including rotated PDF pages.
        const origin = viewport.convertToPdfPoint(0, 0);
        const target = viewport.convertToPdfPoint(movement[0] * viewport.scale, -movement[1] * viewport.scale);
        const step = event.shiftKey ? 10 : 1;
        setPosition(Number(xInput.value) + (target[0] - origin[0]) * step, Number(yInput.value) + (target[1] - origin[1]) * step);
    });
    const drop = el('file-drop');
    ['dragenter','dragover'].forEach(type => drop.addEventListener(type, event => { event.preventDefault(); drop.classList.add('dragging'); }));
    ['dragleave','drop'].forEach(type => drop.addEventListener(type, () => drop.classList.remove('dragging')));
    drop.addEventListener('drop', event => { event.preventDefault(); if (!event.dataTransfer.files.length) return; input.files = event.dataTransfer.files; input.dispatchEvent(new Event('change', { bubbles: true })); });
    form.addEventListener('submit', event => {
        if (submitting || selectingFile) { event.preventDefault(); return; }
        const problem = fileProblem(Array.from(input.files), format());
        showProblem(problem);
        if (problem) { event.preventDefault(); input.focus(); return; }
        if (pdfSelected() && !hiddenVisual.checked && ((xInput.value === '') !== (yInput.value === ''))) {
            event.preventDefault(); showProblem('Enter both X and Y coordinates, or clear both for automatic placement.'); (xInput.value === '' ? xInput : yInput).focus(); return;
        }
        submitting = true;
        el('start-signing').disabled = true;
        el('start-signing').textContent = 'Preparing your document…';
    });
    window.addEventListener('pageshow', () => { submitting = false; el('start-signing').disabled = false; updateSummary(); });
    if (window.ResizeObserver) {
        let timer;
        new ResizeObserver(entries => {
            const width = entries[0].contentRect.width;
            if (dimensions === width) return;
            dimensions = width;
            clearTimeout(timer);
            timer = setTimeout(() => { if (documentPdf) renderPage().catch(() => {}); }, 100);
        }).observe(stage);
    }
    updateSummary();
    updateAppearance();
    input.accept = pdfSelected() ? '.pdf,application/pdf' : format() === 'enveloped-xades' ? '.xml,text/xml,application/xml' : '';
    if (el('validation-errors')) el('validation-errors').focus();
})();
