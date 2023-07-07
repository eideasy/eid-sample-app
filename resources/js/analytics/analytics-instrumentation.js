import Amplitude from './Amplitude.js';
import Analytics from "./Analytics";

(function () {
    const apiKey = document.currentScript.getAttribute('data-api-key');

    document.addEventListener("DOMContentLoaded", () => {
        document.tracker = new Analytics(document, new Amplitude(apiKey))
    });
})()
