import Vue from 'vue';
import Vuetify from '../plugins/vuetify';
import VuetifyMask from '../plugins/vuetify-mask';
import App from './App.vue';
import Vuelidate from 'vuelidate';
import router from './router';
import VueSweetalert2 from 'vue-sweetalert2';
import DatetimePicker from 'vuetify-datetime-picker'
import 'sweetalert2/dist/sweetalert2.min.css';
import excel from 'vue-excel-export';
// import Echo from 'laravel-echo';
import VueSocketio from 'vue-socket.io';
import CKEditor from 'ckeditor4-vue';

Vue.use(VueSocketio, 'http://localhost:3000');

Vue.use(excel);
Vue.use(VuetifyMask);
Vue.use(VueSweetalert2);
Vue.use(Vuetify);
Vue.use(Vuelidate);
Vue.use(DatetimePicker);
Vue.use( CKEditor );

// window.Pusher = require('pusher-js');

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: process.env.MIX_PUSHER_APP_KEY,
//     wsHost: window.location.hostname,
//     wsPort: 6001,
//     forceTLS: false,
//     disableStats: true,
// });


const app = new Vue({
    vuetify: Vuetify,
    el: '#app',
    router,
    render: h => h(App)
});
