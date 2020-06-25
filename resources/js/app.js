import ErrorMessage from "./components/ErrorMessage";
import EmbeddedLogin from "./components/embedded-login/EmbeddedLogin";
import UserData from "./components/UserData";

require('./bootstrap');

window.Vue = require('vue');

Vue.component('embedded-login', EmbeddedLogin);
Vue.component('error-message', ErrorMessage);
Vue.component('user-data', UserData)

new Vue({}).$mount('#app');

