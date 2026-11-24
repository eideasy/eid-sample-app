# Sample app redesign QA

Date: 18 November 2026

final result: passed

## Scope and visual truth

Existing Laravel application, rebuilt in place on `EID-4367-redesign-sample-app`. The first displayed signing design was used as the baseline, alongside the existing wallet playground. This is a production UI improvement with the existing signing capabilities retained, rather than a pixel-for-pixel clone of the generated mock. The login widget redesign was explicitly deferred.

Source visual truth: `/Users/andriihrynchuk/.codex/generated_images/01a1edae-5e78-7c72-ba15-89c52a5d0a8c/exec-440d27ec-6eb6-4164-a5ee-d3e1ea882c6b.png`

Implementation: `https://eid-sample-app.ddev.site/sign-custom-file`

Final implementation screenshot: `/private/tmp/eid-sample-redesign/16-signing-verified-desktop.jpg`

Full-view comparison: `/private/tmp/eid-sample-redesign/comparison-final.jpg`

Focused placement-controls comparison: `/private/tmp/eid-sample-redesign/comparison-controls.jpg`

## Normalization and state

- Source pixels: 1487 × 1058. Normalized to 1440 × 1024 for the comparison sheet; the near-identical aspect ratios differ by less than 0.2%.
- Implementation: 1440 × 1024 CSS viewport and screenshot pixels, 1 image pixel per CSS pixel. The PDF canvas separately accounts for display density.
- State: hosted signing, PDF output, one selected two-page fictional PDF, page 1, bottom-right placement, optional sections closed, visual signature enabled.
- Intentional content differences: the implementation renders a real portrait PDF with actual page geometry. The mock displays a wider fictional document. Correct PDF point coordinates therefore differ from the illustrative numbers in the mock. The marker is a placement guide, not a promised final signature image.
- Mobile evidence: `/private/tmp/eid-sample-redesign/12-signing-final-mobile.jpg`, captured at 390 × 844 CSS pixels, full-page capture. Document and viewport widths both measured 390 pixels.

## Comparison history

1. First loaded-document comparison: `/private/tmp/eid-sample-redesign/comparison-first.jpg` from `/private/tmp/eid-sample-redesign/08-signing-first-pass.jpg`.
   - [P2] Loaded-file controls occupied too much vertical space, pushing configuration and placement controls too far down compared with the reference.
   - Fix: compact the loaded-file area, remove duplicated upload messaging in that state, reduce heading spacing and preview height, retain a reachable desktop action bar. Keep the action bar in normal flow on mobile.
   - [P2] Secondary integration notes added unnecessary height to the primary setup panel.
   - Fix: move supporting integration notes below the main workspace and action.
2. Post-fix comparison: `/private/tmp/eid-sample-redesign/comparison-final.jpg`. The two-column setup/preview hierarchy, grouped choices, placement controls and primary action are clear. PDF geometry is preserved. The focused comparison confirms readable numeric fields, a page control and presets. Optional content uses ordinary scrolling; it is not clipped from the document.
3. Interaction correction: XML mode initially displayed a ready-state preview message even when a PDF was selected. It now mirrors the explicit file/format error. The final build was checked in the browser and blocked submission without navigation, then returned successfully to the normal PDF preview.

## Required fidelity surfaces

- Fonts and typography: uses the existing self-hosted FK Grotesk and FK Display fonts. Heading scale, weight and tight display spacing follow the wallet design. Form labels and supporting hints use the existing product's smaller settings typography. No clipped labels were observed.
- Spacing and layout rhythm: restrained borders, 22px panels, consistent section padding, desktop setup/preview columns and a single-column mobile layout. The loaded-document state is more compact after the QA fix. Page dimensions remain accurate rather than stretching the PDF to resemble the mock.
- Colors and tokens: mint `#f2faf7`, forest `#193f35`, sage `#eaf1df`, quiet green borders and visible focus outlines. The hub's featured card and primary actions use forest green.
- Image quality and asset fidelity: original eID Easy logo, library-provided Lucide icons and actual PDF.js canvas rendering. No generated screenshot is used as a functioning page or a document placeholder. The sample PDF contains clearly fictional, nonbinding text.
- Copy and content: all five existing signing flows and four output formats remain available. Descriptions distinguish hosted/API/digest flows, note the PDF helper, and identify sandbox e-Seals. Appearance instructions explain bottom-left point coordinates and service-side adjustments. The API receives zero-based pages; the UI uses human page numbers.

## Interactions and validation checked

- Hub navigation to signing and ASiC-E add-signature pages.
- Fictional sample PDF selection and rendering, file feedback and local preview.
- Bottom-left/right presets, pointer click placement, dragging and keyboard movement.
- Next-page navigation including a rotated second page; numeric values remain synchronized.
- Visual signature visibility toggle.
- ASiC-E/XML format changes hide and exclude PDF fields.
- Invalid XML/PDF combination shows an error and cannot submit.
- Flow selection changes descriptions, summary and action label.
- Mobile placement controls and stacked layout; no horizontal overflow.
- Updated return/download view rendered with a synthetic reference; no real download was requested.
- Fresh final browser log check: no warnings or errors. Missing-key Amplitude initialization was guarded after an earlier local configuration error.
- Production build passed. PHP suite: 25 tests, 137 assertions. JavaScript suite: 3 tests passed. `git diff --check` passed.

## Evidence and limits

Additional captures: `/private/tmp/eid-sample-redesign/07-catalogue-after.jpg`, `/private/tmp/eid-sample-redesign/13-catalogue-mobile.jpg`, `/private/tmp/eid-sample-redesign/14-add-signature-mobile.jpg`, `/private/tmp/eid-sample-redesign/15-download-desktop.jpg`.

No live signature, e-Seal, identity verification or signer invitation was completed. Signing service calls were mocked in backend tests. The native browser-automation file chooser stalled, so PDF rendering and file-change behavior were exercised through the product's real sample-file action. Personal-file picker behavior still benefits from a manual check. Encrypted/unsupported PDF fallback exists but was not tested with every PDF variant. Full screen-reader and cross-browser accessibility certification was not performed.

## Follow-up polish

- [P3] The correct portrait PDF occupies less horizontal space than the mock's fictional landscape document. A future zoom/fullscreen control could improve document reading without changing placement semantics.
- [P3] The source mock places appearance controls on one line; the implementation uses labeled rows to retain the page selector, automatic placement and explicit API convention. This is an intentional usability tradeoff.

## Implementation checklist

- [x] Explain the demo journeys with cards.
- [x] Rebuild signing setup and PDF placement.
- [x] Preserve current integration choices and server-side secrets.
- [x] Improve validation, settings recovery and related pages.
- [x] Refresh locked PHP dependencies in DDEV; deprecation notices no longer appear.
- [x] Keep the deferred login-widget layout and source unchanged.
- [x] Build, test, compare and check desktop/mobile states.

## Follow-up: dropdown chevron spacing

The signing-flow select uses a library chevron inset 14px from the right border, with 44px right padding to protect the label. The native select retains its keyboard and selection behavior. Verified switching between digest and hosted signing, measured the 14px inset in the browser, and rebuilt production assets successfully. Evidence: `/private/tmp/eid-sample-redesign/17-chevron-inset.jpg`.
