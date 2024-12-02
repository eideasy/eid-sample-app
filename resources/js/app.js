import ErrorMessage from "./components/ErrorMessage";
import UserData from "./components/UserData";
import EmbeddedSigning from "./components/embedded-signing/EmbeddedSigning";
import EideasyWidget from "./components/EideasyWidget";

require('./bootstrap');

window.Vue = require('vue');

Vue.component('embedded-signing', EmbeddedSigning);
Vue.component('error-message', ErrorMessage);
Vue.component('user-data', UserData)
Vue.component('widget', EideasyWidget)

new Vue({
    el: '#app',
    data: {
        isSuccess: false,
    },
    methods: {
        handleSuccess() {
            this.isSuccess = true;
        },
        handleFail() {
            this.isSuccess = false;
        },
    },
    components: {
        widget: EideasyWidget,
    },
});
