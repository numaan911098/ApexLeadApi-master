import Vue from 'vue';
import KeenUI from '../libs/keenui/keen-ui.min.js';
import VueLocalStorage from 'vue-ls';
import VeeValidate from 'vee-validate';
import VueResource from 'vue-resource'

Vue.use(KeenUI)
Vue.use(VueResource)
Vue.use(VueLocalStorage, { namespace: 'leadgen__'})
Vue.use(VeeValidate)
Vue.config.productionTip = false

Vue.component('LEADGEN_FORM_TAG', require('./components/FormGenerator.vue').default);

var app = new Vue({
  el: '#leadgen-form-wrap-LEADGEN_FORM_KEY',
})
