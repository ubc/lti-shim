/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('../bootstrap')

import Vue from 'vue'

// vue material design icons
import 'vue-material-design-icons/styles.css'

// vue-good-table
import './plugins/vue-good-table/vue-good-table'

// vuex
import store from './plugins/store/store'

// we'll use axios here to establish csrf protection first
import axios from 'axios'
axios.get('/sanctum/csrf-cookie')
  .then(response => {})
  .catch(response => {
    console.log('Failed to establish CSRF protection');
    return Promise.reject(response)
  })

Vue.component('instructor-main-view',
  require('./views/InstructorMainView.vue').default)
Vue.component('first-time-setup-view',
  require('./views/FirstTimeSetupView.vue').default)

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
  store
}).$mount('#app')
