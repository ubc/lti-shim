import axios from 'axios'
import Vue from 'vue'

const LOGIN_URL = '/login'
const LOGOUT_URL = '/logout'
const CSRF_URL = '/sanctum/csrf-cookie'

// calls Laravel Fortify provided methods for authentication, also will
// initialize the CSRF token

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
    csrf(context) {
      // axios should automatically set the CSRF header from the returned
      // values
      return axios.get(CSRF_URL)
        .then(response => {
        })
        .catch(response => {
          // TODO: show error msg
          return Promise.reject(response)
        })
    },
    login(context, credential) {
      // TODO: check required fields exist in credential
      return context.dispatch('csrf').then(() => {
        axios.post(LOGIN_URL, credential)
          .then(response => {
            // now we can create a new token
            context.commit('setLoggedIn')
            return response
          })
          .catch(response => {
            // TODO: fix error msg
            //Vue.notify({'title': 'Failed to retrieve existing API tokens',
            //  'type': 'error'})
            return Promise.reject(response)
          })
      })
    },
    logout(context) {
      axios.post(LOGOUT_URL)
        .then(response => {
          context.commit('setLoggedOut')
          return response
        })
        .catch(response => {
          // TODO: show error message
          return Promise.reject(response)
        })
    }
  }
}

export default auth
