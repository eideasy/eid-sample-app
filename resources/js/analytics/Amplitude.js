import * as amplitude from '@amplitude/analytics-browser';
import Tracker from "./Tracker";

export default class Amplitude extends Tracker {
    constructor(apiKey) {
        amplitude.init(apiKey, null, {
            serverZone: 'EU',
            defaultTracking: {
                fileDownloads: false,
                pageViews: true,
                sessions: true,
                // disabling form interaction since its not working well with our forms currently.
                // Need to investigate further.
                formInteractions: false,
            },
        });
        super();
    }

    /**
     * @returns {Promise<unknown>}
     */
    track(eventName, properties = {}) {
        return amplitude.track(eventName, properties).promise;
    }
}
