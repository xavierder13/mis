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

Vue.use(excel);
Vue.use(VuetifyMask);
Vue.use(VueSweetalert2);
Vue.use(Vuetify);
Vue.use(Vuelidate);
Vue.use(DatetimePicker);


const app = new Vue({
    vuetify: Vuetify,
    el: '#app',
    router,
    render: h => h(App)
});
