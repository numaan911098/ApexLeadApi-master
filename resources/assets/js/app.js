
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

// require('./bootstrap');
import Vue from 'vue';
import KeenUI from 'keen-ui';
import VueLocalStorage from 'vue-ls';
import VeeValidate from 'vee-validate';
import VueResource from 'vue-resource'

// register plugins
Vue.use(KeenUI)
Vue.use(VueResource)
Vue.use(VueLocalStorage, { namespace: 'leadgen__'})
Vue.use(VeeValidate)

// configure plugins
Vue.config.productionTip = false
Vue.http.options.root = window.apiBaseUrl

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component('form-generator', require('./components/form/FormGenerator.vue'));
Vue.component('landingpage-generator', require('./components/landingpage/LandingpageGenerator.vue'));

const app = new Vue({
    el: '#app'
});
