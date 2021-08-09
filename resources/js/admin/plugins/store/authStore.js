import axios from 'axios'
import Vue from 'vue'

// calls Laravel Fortify provided methods for authentication, also will
// initialize the CSRF token

const LOGIN_URL = '/login'
const LOGOUT_URL = '/logout'
const CSRF_URL = '/sanctum/csrf-cookie'

// for storing login status in localstorage, which can only hold strings
const LOGIN_STATUS_KEY = 'loginStatus'
const LOGIN_STATUS_TRUE = 'loggedIn'
const LOGIN_STATUS_FALSE = 'loggedOut'

const auth = {
  namespaced: true,
  state: {
    isLoggedIn: false
  },
  mutations: {
    setLoggedIn(state) {
      state.isLoggedIn = true
    },
    setLoggedOut(state) {
      state.isLoggedIn = false
    }
  },
  getters: {
  },
  actions: {
    csrf() {
      // axios should automatically set the CSRF header from the returned
      // values
      return axios.get(CSRF_URL)
        .then(response => {
        })
        .catch(response => {
          Vue.notify({'title': 'Failed to establish CSRF protection',
                      'type': 'error'})
          return Promise.reject(response)
        })
    },
    login(context, credential) {
      // TODO: check required fields exist in credential
      return context.dispatch('csrf').then(() => {
        return axios.post(LOGIN_URL, credential)
          .then(response => {
            localStorage.setItem(LOGIN_STATUS_KEY, LOGIN_STATUS_TRUE)
            context.commit('setLoggedIn')
            return response
          })
          .catch(response => {
            Vue.notify({'title': 'Login Failed', 'type': 'error'})
            return Promise.reject(response)
          })
      })
    },
    logout(context, isInvalidSession = false) {
      if (context.state.isLoggedIn) {
        context.commit('setLoggedOut')
        localStorage.setItem(LOGIN_STATUS_KEY, LOGIN_STATUS_FALSE)
        axios.post(LOGOUT_URL)
        if (isInvalidSession) {
          Vue.notify({'title': 'Invalid session, please login', 'type': 'error'})
        }
      }
    }
  }
}

// restore login status on page load
if (localStorage.getItem(LOGIN_STATUS_KEY)) {
  let loginStatus = localStorage.getItem(LOGIN_STATUS_KEY)
  if (loginStatus == LOGIN_STATUS_TRUE) auth.state.isLoggedIn = true
}

export default auth
