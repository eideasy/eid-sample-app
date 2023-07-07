class Analytics {

    eventNameAttribute = 'data-event-name';
    eventNameAttributeOnchange = 'data-event-name-onchange';
    DOM;

    /**
     * @var {Tracker}
     */
    tracker;

    /**
     * @param el
     * @param {Tracker} tracker
     */
    constructor(el, tracker) {
        this.DOM = { el: el };
        this.tracker = tracker;

        this.init();
    }

    init() {
        this.DOM.clickableElements = this.DOM.el.querySelectorAll(`[${this.eventNameAttribute}]`);
        this.DOM.changeableElements = this.DOM.el.querySelectorAll(`[${this.eventNameAttributeOnchange}]`);

        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('action-ref')) {
            this.track(urlParams.get('action-ref'));

            // To prevent duplicate tracking, we remove the action-ref from the URL.
            urlParams.delete('action-ref');

            const urlParamsString = urlParams.toString().trim();
            window.history.replaceState(null, '',
              window.location.pathname.concat(urlParamsString.length ? '?'.concat(urlParams.toString()) : '')
            );
        }

        this.DOM.clickableElements.forEach((element) => {
            this.attachClickEvents(element);
        });
        this.DOM.changeableElements.forEach((element) => {
            this.attachChangeEvents(element);
        });
    }

    track(eventName, properties = {}) {
        return this.tracker.track(eventName, properties);
    }

    handleClick(event, element) {
        const dataEventName = element.getAttribute(this.eventNameAttribute);
        const target = element.getAttribute('target');
        const href = element.getAttribute('href');

        // If redirect is made to different page, we need to wait for the event to be sent.
        if (target === null && href && href.includes('.')) {
            event.preventDefault();

            this.track(dataEventName)
                .then(() => {
                    document.location.href = href;
                });

            // Failsafe, in case promise is not resolved.
            setTimeout(() => {document.location.href = href}, 100);
        } else {
            this.track(dataEventName);
        }
    }

    handleChange(event, element) {
        const dataEventName = element.getAttribute(this.eventNameAttributeOnchange);
        this.track(dataEventName);
    }

    attachClickEvents(element) {
        element.addEventListener('click', (event) => {
            this.handleClick(event, element);
        });
    }

    attachChangeEvents(element) {
        element.addEventListener('change', (event) => {
            this.handleChange(event, element);
        });
    }
}

export default Analytics;
