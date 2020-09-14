import ErrorMessage from "./components/ErrorMessage";
import EmbeddedLogin from "./components/embedded-login/EmbeddedLogin";
import UserData from "./components/UserData";
import EmbeddedSigning from "./components/embedded-signing/EmbeddedSigning";

require('./bootstrap');

window.Vue = require('vue');

Vue.component('embedded-login', EmbeddedLogin);
Vue.component('embedded-signing', EmbeddedSigning);
Vue.component('error-message', ErrorMessage);
Vue.component('user-data', UserData)

new Vue({}).$mount('#app');

