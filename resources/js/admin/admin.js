/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('../bootstrap');

import Vue from 'vue'

// vuex
import store from './plugins/store/store'

// vue-notification
import notification from './plugins/notification/notification'

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

// we only need to register the root component since that's the only one we'll
// be using in blade templates
Vue.component('root', require('./views/Root.vue').default);

// Intercept unauthenticated requests and send them to login
axios.interceptors.response.use(
  response => { return response },
  error => {
    if (error.response.status === 401) {
      store.dispatch('auth/logout', true)
      const path = '/login'
      if (router.path !== path) router.push(path)
    }
    return Promise.reject(error)
  }
)

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
  store,
  router
}).$mount('#app')
