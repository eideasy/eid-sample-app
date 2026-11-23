// PDF coordinates are points from the page's bottom-left corner, not CSS pixels.
function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

// Reserve room for the generated signature, whose size varies with the signer and provider.
const signatureWidth = 180;
const signatureHeight = 72;
const pageInset = 12;

function placementArea([left, bottom, right, top]) {
    // DSS converts Y using the page height. On an offset page that can give a
    // different upper limit from the absolute PDF coordinate shown in the viewer.
    return {
        left: Math.ceil(Math.max(0, left) + pageInset),
        bottom: Math.ceil(Math.max(0, bottom) + pageInset),
        right: Math.floor(right - pageInset),
        top: Math.floor(Math.min(top, top - bottom) - pageInset),
    };
}

function fitsPlacement(x, y, area) {
    return Number.isInteger(x) && Number.isInteger(y)
        && x >= area.left && x + signatureWidth <= area.right
        && y >= area.bottom && y + signatureHeight <= area.top;
}

function pageIndex(displayPage, pageCount) {
    return clamp(Math.trunc(Number(displayPage) || 1), 1, pageCount) - 1;
}

function fileProblem(files, format) {
    if (!files.length) return 'Choose at least one file to continue.';
    if (format === 'pdf') {
        if (files.length !== 1) return 'PDF signing accepts one file at a time.';
        if (files[0].type !== 'application/pdf' && !/\.pdf$/i.test(files[0].name)) return 'Choose a PDF for this format.';
    }
    if (format === 'enveloped-xades' && files.some(file => !['application/xml', 'text/xml'].includes(file.type) && !/\.xml$/i.test(file.name))) return 'The XML format requires XML files.';
    return '';
}

// Promise chaining keeps this CommonJS helper compatible with the Webpack 4 build.
function documentFingerprint(file, cryptoApi = globalThis.crypto) {
    if (!file || !cryptoApi?.subtle) return Promise.resolve('');
    return Promise.resolve().then(() => file.arrayBuffer())
        .then(data => cryptoApi.subtle.digest('SHA-256', data))
        .then(digest => Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, '0')).join(''))
        .catch(() => '');
}

module.exports = { clamp, placementArea, fitsPlacement, signatureWidth, signatureHeight, pageIndex, fileProblem, documentFingerprint };
