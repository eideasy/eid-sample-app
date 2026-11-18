// PDF coordinates are points from the page's bottom-left corner, not CSS pixels.
function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function fitPlacement(x, y, bounds, width = 180, height = 60) {
    const left = Math.max(0, bounds[0]);
    const bottom = Math.max(0, bounds[1]);
    const w = Math.min(width, bounds[2] - left);
    const h = Math.min(height, bounds[3] - bottom);
    return { x: Math.round(clamp(x, left, bounds[2] - w)), y: Math.round(clamp(y, bottom, bounds[3] - h)), width: w, height: h };
}

function pageIndex(displayPage, pageCount) {
    return clamp(Math.trunc(Number(displayPage) || 1), 1, pageCount) - 1;
}

function fileProblem(files, format) {
    if (!files.length) return 'Choose at least one file to continue.';
    if (format === 'pdf') {
        if (files.length !== 1) return 'PDF signing accepts one PDF. Choose one file or use an ASiC-E format for multiple files.';
        if (files[0].type !== 'application/pdf' && !/\.pdf$/i.test(files[0].name)) return 'Choose a PDF, or switch to ASiC-E to sign this file.';
    }
    if (format === 'enveloped-xades' && files.some(file => !/\.xml$/i.test(file.name))) return 'Enveloped XAdES requires XML files. Choose XML or use an ASiC-E format.';
    return '';
}

module.exports = { clamp, fitPlacement, pageIndex, fileProblem };
