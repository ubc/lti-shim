/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('../bootstrap');

import Vue from 'vue'

// vuex
import store from './plugins/store/store'

/*
// vue-notification
import notification from './plugins/notification/notification'
*/

// vue-router
import router from './plugins/router/router'

// vue material design icons
import 'vue-material-design-icons/styles.css'


/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

Vue.component('root', require('./views/Root.vue').default);
//Vue.component('admin-main', require('./components/AdminMain.vue').default);
//Vue.component('session-dropdown',
//  require('./components/SessionDropdown.vue').default);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
  store,
  router
}).$mount('#app')
