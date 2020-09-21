/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

// vue material design icons
import 'vue-material-design-icons/styles.css';

// vue-good-table
import './plugins/vue-good-table/vue-good-table';

Vue.component('midway-main',
  require('./midway/MidwayMain.vue').default);
Vue.component('user-list', require('./midway/UserList.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
  el: '#app',
  //store
});
