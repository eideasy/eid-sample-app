const { clamp, placementArea, fitsPlacement, signatureWidth, signatureHeight, pageIndex, fileProblem, documentFingerprint } = require('./signature-coordinates');

(function () {
    const form = document.getElementById('signing-playground');
    if (!form) return;
    const el = id => document.getElementById(id);
    const input = el('unsigned_file');
    const formatSuggestion = el('suggested-format');
    const xInput = el('pdf_x');
    const yInput = el('pdf_y');
    const pageInput = el('pdf_page');
    const pageIndexInput = el('pdf_page_index');
    const stage = el('preview-stage');
    const paper = el('pdf-page');
    const marker = el('signature-marker');
    const safeArea = el('signature-safe-area');
    const placementError = el('placement-error');
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
    let loadingPreview = false;
    let loadingPage = false;
    let placementProblem = '';
    let selectionGeneration = 0;
    let sampleGeneration = 0;
    const format = () => form.querySelector('input[name="containerType"]:checked').value;
    const pdfSelected = () => format() === 'pdf';
    const appearanceAvailable = () => pdfSelected() && flow.value !== 'digest';
    const hasCustomPosition = () => xInput.value !== '' && yInput.value !== '';
    const describe = message => { el('preview-message').textContent = message; };

    function updateSubmitAvailability() {
        el('start-signing').disabled = submitting || selectingFile || loadingPreview || loadingPage || Boolean(placementProblem);
    }

    function showProblem(message, suggestedFormat = '') {
        el('file-error').textContent = message;
        el('file-feedback').hidden = !message;
        formatSuggestion.hidden = !suggestedFormat;
        formatSuggestion.value = suggestedFormat;
        formatSuggestion.textContent = suggestedFormat === 'pdf' ? 'Switch to PDF' : 'Switch to ASiC-E';
        input.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function updateFormatControls() {
        const selected = format();
        form.querySelectorAll('input[name="containerType"]').forEach(option => {
            if (option.value === 'enveloped-xades' || option.value === 'asice-for-each-file') {
                option.disabled = flow.value === 'digest';
            }
        });
        if (flow.value === 'digest' && selected !== 'pdf' && selected !== 'asice') {
            form.querySelector('input[name="containerType"][value="asice"]').checked = true;
        }
        form.querySelectorAll('[data-format]').forEach(node => { node.hidden = node.dataset.format !== format(); });
        input.accept = pdfSelected() ? '.pdf,application/pdf' : format() === 'enveloped-xades' ? '.xml,text/xml,application/xml' : '';
    }

    function validateFiles({ requireFiles = false } = {}) {
        const files = Array.from(input.files);
        const problem = files.length || requireFiles ? fileProblem(files, format()) : '';
        // Prefer a signed PDF for a single PDF. ASiC-E keeps every file in one bundle.
        const suggestedFormat = problem && files.length ? (fileProblem(files, 'pdf') ? 'asice' : 'pdf') : '';
        showProblem(problem, suggestedFormat);
        return problem;
    }

    formatSuggestion.addEventListener('click', () => {
        const option = form.querySelector(`input[name="containerType"][value="${formatSuggestion.value}"]`);
        if (option) {
            option.click();
            option.focus();
        }
    });

    function updateSummary() {
        const count = input.files.length;
        const flowName = flow.options[flow.selectedIndex].text;
        const formatName = form.querySelector('input[name="containerType"]:checked + span').textContent;
        el('signing-summary').textContent = count ? `${flowName} · ${formatName} · ${count} ${count === 1 ? 'file' : 'files'}` : 'Choose a file to get started.';
        el('start-signing').firstChild.textContent = flow.value === 'eseal' ? 'Create sandbox e-Seal ' : flow.value === 'multisign' ? 'Set up signers ' : 'Continue to signing ';
    }

    function positionOverlay(overlay, bounds) {
        const [x1, y1, x2, y2] = viewport.convertToViewportRectangle(bounds);
        overlay.style.left = `${Math.min(x1, x2)}px`;
        overlay.style.top = `${Math.min(y1, y2)}px`;
        overlay.style.width = `${Math.abs(x2 - x1)}px`;
        overlay.style.height = `${Math.abs(y2 - y1)}px`;
    }

    function updateMarker() {
        const editable = appearanceAvailable() && !hiddenVisual.checked;
        const area = page && viewport ? placementArea(page.view) : null;
        const areaFits = area && fitsPlacement(area.left, area.bottom, area);
        const hasCoordinate = xInput.value !== '' || yInput.value !== '';
        const x = Number(xInput.value);
        const y = Number(yInput.value);
        placementProblem = '';
        if (editable && hasCoordinate && input.files.length) {
            if (!hasCustomPosition()) {
                placementProblem = 'Enter both coordinates, or choose Automatic to clear them.';
            } else if (!area && !loadingPreview && !loadingPage && !selectingFile) {
                placementProblem = 'Manual placement requires a PDF preview. Choose Automatic or try another PDF.';
            } else if (area && !areaFits) {
                placementProblem = 'This page is too small for manual placement. Choose another page, Automatic, or hide the visual signature.';
            } else if (area && !fitsPlacement(x, y, area)) {
                placementProblem = 'Move the entire signature inside the dashed safe area, or choose Automatic.';
            }
        }
        placementError.hidden = !placementProblem;
        if (placementError.textContent !== placementProblem) placementError.textContent = placementProblem;
        [xInput, yInput].forEach(field => field.setAttribute('aria-invalid', placementProblem ? 'true' : 'false'));
        updateSubmitAvailability();

        safeArea.hidden = !editable || !areaFits;
        if (!safeArea.hidden) positionOverlay(safeArea, [area.left, area.bottom, area.right, area.top]);
        marker.hidden = !editable || !area || !hasCustomPosition();
        marker.classList.toggle('placement-invalid', Boolean(placementProblem));
        if (marker.hidden) return;

        // Keep the marker reachable even if the typed coordinates are far outside the page.
        const [left, bottom, right, top] = page.view;
        const markerX = clamp(x, left, Math.max(left, right - signatureWidth));
        const markerY = clamp(y, bottom, Math.max(bottom, top - signatureHeight));
        positionOverlay(marker, [markerX, markerY, markerX + signatureWidth, markerY + signatureHeight]);
        marker.classList.toggle('compact', Number.parseFloat(marker.style.width) < 130);
    }

    function setPosition(x, y) {
        if (!page) return;
        const [left, bottom, right, top] = page.view;
        xInput.value = Math.round(clamp(x, Math.max(0, left), Math.max(0, right)));
        yInput.value = Math.round(clamp(y, Math.max(0, bottom), Math.max(0, top)));
        pageIndexInput.value = currentPage - 1;
        pageInput.value = currentPage;
        updateMarker();
    }

    function updateAppearance() {
        const pdf = pdfSelected();
        const available = appearanceAvailable();
        el('appearance-controls').disabled = !available;
        el('appearance-controls').hidden = !available;
        el('non-pdf-note').hidden = pdf;
        el('digest-appearance-note').hidden = !pdf || available;
        [xInput, yInput, pageInput, pageIndexInput].forEach(field => {
            field.disabled = !available || hiddenVisual.checked;
        });
        el('placement-fields').hidden = hiddenVisual.checked;
        const area = page ? placementArea(page.view) : null;
        form.querySelectorAll('[data-preset]').forEach(button => {
            button.disabled = button.dataset.preset !== 'auto' && (!area || !fitsPlacement(area.left, area.bottom, area));
        });
        updateMarker();
    }

    async function renderPage() {
        if (!documentPdf) return;
        const revision = ++renderGeneration;
        const currentDocument = documentPdf;
        loadingPage = true;
        page = null;
        pointerOffset = null;
        updateAppearance();
        updateSubmitAvailability();
        try {
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
            updateAppearance();
            renderTask = page.render({ canvasContext: canvas.getContext('2d'), viewport, transform: density === 1 ? null : [density, 0, 0, density, 0, 0] });
            pendingRender = renderTask.promise;
            try { await pendingRender; } catch (error) { if (error.name !== 'RenderingCancelledException') throw error; }
        } catch (error) {
            if (revision !== renderGeneration) return;
            currentDocument.destroy();
            documentPdf = null;
            loadingTask = null;
            showPreviewUnavailable();
        } finally {
            if (revision === renderGeneration) {
                loadingPage = false;
                updateMarker();
            }
        }
    }

    function showPreviewUnavailable() {
        paper.hidden = true;
        page = null;
        viewport = null;
        el('preview-empty').hidden = false;
        el('page-navigation').hidden = true;
        pageInput.value = currentPage;
        pageIndexInput.value = currentPage - 1;
        xInput.removeAttribute('max');
        yInput.removeAttribute('max');
        describe('This PDF could not be previewed. It may be encrypted or unsupported. Choose another file or use automatic placement.');
        updateAppearance();
    }

    async function loadPreview() {
        const revision = ++generation;
        ++renderGeneration;
        loadingPreview = false;
        loadingPage = false;
        updateSubmitAvailability();
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
        currentPage = Math.max(1, Math.trunc(Number(pageInput.value) || 1));
        loadingPreview = true;
        updateSubmitAvailability();
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
            if (revision === generation) showPreviewUnavailable();
        } finally {
            if (revision === generation) {
                loadingPreview = false;
                updateMarker();
            }
        }
    }

    async function fileChange() {
        ++sampleGeneration;
        const revision = ++selectionGeneration;
        ++generation;
        ++renderGeneration;
        const fingerprintInput = el('placement-document-fingerprint');
        const fingerprint = fingerprintInput.value;
        fingerprintInput.value = '';
        selectingFile = true;
        updateSubmitAvailability();
        // Old input includes this local content fingerprint after validation.
        // A different PDF must reset placement even if it reuses the filename.
        const files = Array.from(input.files);
        const selectedFingerprint = fileProblem(files, 'pdf') ? '' : await documentFingerprint(files[0]);
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
        validateFiles();
        updateSummary();
        try { await loadPreview(); }
        finally {
            if (revision === selectionGeneration) {
                selectingFile = false;
                updateMarker();
            }
        }
    }

    input.addEventListener('change', fileChange);
    el('use-sample-file').addEventListener('click', async event => {
        const button = event.currentTarget;
        const revision = ++sampleGeneration;
        button.disabled = true;
        try {
            const response = await fetch('/samples/signing-demo.pdf');
            if (!response.ok) throw new Error('Sample unavailable');
            const blob = await response.blob();
            if (revision !== sampleGeneration || submitting) return;
            const transfer = new DataTransfer();
            transfer.items.add(new File([blob], 'sample-agreement.pdf', { type: 'application/pdf' }));
            input.files = transfer.files;
            form.querySelector('input[name="containerType"][value="pdf"]').checked = true;
            updateFormatControls();
            input.dispatchEvent(new Event('change', { bubbles: true }));
        } catch (error) {
            if (revision === sampleGeneration && !submitting) {
                showProblem('The sample could not be loaded. Please choose a file instead.');
            }
        }
        finally { button.disabled = false; }
    });
    flow.addEventListener('change', () => {
        form.querySelectorAll('[data-flow]').forEach(node => { node.hidden = node.dataset.flow !== flow.value; });
        updateFormatControls();
        validateFiles();
        updateAppearance();
        updateSummary();
    });
    form.querySelectorAll('input[name="containerType"]').forEach(radio => radio.addEventListener('change', () => {
        ++sampleGeneration;
        updateFormatControls();
        validateFiles();
        updateSummary();
        loadPreview();
    }));
    hiddenVisual.addEventListener('change', updateAppearance);
    [xInput, yInput].forEach(field => field.addEventListener('input', updateMarker));
    pageInput.addEventListener('change', async () => {
        if (documentPdf) { currentPage = pageIndex(pageInput.value, documentPdf.numPages) + 1; await renderPage(); }
        else pageIndexInput.value = Math.max(0, Math.trunc(Number(pageInput.value) || 1) - 1);
    });
    el('previous-page').addEventListener('click', async () => { currentPage = Math.max(1, currentPage - 1); await renderPage(); });
    el('next-page').addEventListener('click', async () => { currentPage = Math.min(documentPdf.numPages, currentPage + 1); await renderPage(); });
    form.querySelectorAll('[data-preset]').forEach(button => button.addEventListener('click', () => {
        if (button.dataset.preset === 'auto') {
            xInput.value = '';
            yInput.value = '';
            updateMarker();
            return;
        }
        if (!page) return;
        const area = placementArea(page.view);
        if (!fitsPlacement(area.left, area.bottom, area)) return;
        setPosition(button.dataset.preset === 'left' ? area.left : area.right - signatureWidth, area.bottom);
    }));

    function pointFromEvent(event) {
        const rect = paper.getBoundingClientRect();
        return viewport.convertToPdfPoint((event.clientX - rect.left) * viewport.width / rect.width, (event.clientY - rect.top) * viewport.height / rect.height);
    }
    paper.addEventListener('pointerdown', event => {
        if (!page || !appearanceAvailable() || hiddenVisual.checked || event.button !== 0) return;
        const point = pointFromEvent(event);
        const dragging = marker.contains(event.target) && hasCustomPosition();
        const [left, bottom, right, top] = page.view;
        const anchor = [
            clamp(Number(xInput.value), left, Math.max(left, right - signatureWidth)),
            clamp(Number(yInput.value), bottom, Math.max(bottom, top - signatureHeight)),
        ];
        pointerOffset = dragging ? [point[0] - anchor[0], point[1] - anchor[1]] : [0, 0];
        paper.setPointerCapture(event.pointerId);
        setPosition(point[0] - pointerOffset[0], point[1] - pointerOffset[1]);
        marker.focus({ preventScroll: true });
        event.preventDefault();
    });
    paper.addEventListener('pointermove', event => {
        if (!pointerOffset || !page || !appearanceAvailable()) return;
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
        if (submitting || selectingFile || loadingPreview || loadingPage) { event.preventDefault(); return; }
        const problem = validateFiles({ requireFiles: true });
        if (problem) { event.preventDefault(); input.focus(); return; }
        updateMarker();
        if (placementProblem) { event.preventDefault(); xInput.focus(); return; }
        ++sampleGeneration;
        submitting = true;
        el('start-signing').disabled = true;
        el('start-signing').textContent = 'Preparing your document…';
    });
    window.addEventListener('pageshow', () => { submitting = false; updateSubmitAvailability(); updateSummary(); });
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
    updateFormatControls();
    if (el('validation-errors')) el('validation-errors').focus();
})();
