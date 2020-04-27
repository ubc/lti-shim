// Stores the auth token in vuex, with a backup in html5 storage

// needs to be global axios in order to set common headers for all axios instances
import axios from 'axios'
import Vue from 'vue'

export const TOKEN_KEY = 'LTI Shim Admin Site Token'

const TOKEN_URL = '/oauth/personal-access-tokens'

export const auth = {
  namespaced: true,
  state: {
    token: localStorage.getItem(TOKEN_KEY) || '',
    isTokenRejected: false
  },
  mutations: {
    token(state, token) {
      state.token = token
    },
    rejectToken(state) {
      state.isTokenRejected = true
    },
    acceptToken(state) {
      state.isTokenRejected = false
    }
  },
  getters: {
    isSignedIn: (state) => {
      if (state.token) return true
      return false
    }
  },
  actions: {
    // Get a new API access token
    createToken(context) {
      let data = { name: TOKEN_KEY, scopes: [] }
      axios.post('/oauth/personal-access-tokens', data)
        .then(response => {
          let token = response.data.accessToken
          localStorage.setItem(TOKEN_KEY, token)
          context.commit('token', token)
          context.commit('acceptToken')
          axios.defaults.headers.common['Authorization'] = "Bearer " + token
        })
        .catch (response => {
          Vue.notify({'title': 'Failed to create new API token',
            'type': 'error'})
        });
    },
    // Technically revoking a token, not deleting it
    deleteToken(context, tokenId) {
      axios.delete(TOKEN_URL + '/' + tokenId)
        .catch(response => {
          Vue.notify({'title': 'Failed to delete old API token',
            'type': 'error'})
        })
    },
    // REST calls needs to be authenticated by a token, this is NOT the session
    // cookie we obtained from logging in to the site, but we can use that 
    // session cookie to get this token. It's not an ideal solution, but it
    // was the only way to let us use Laravel's built in auth systems without
    // doing a lot of customization.
    signIn(context) {
      // don't need to do anything if we already have a token
      if (context.getters['isSignedIn']) return Promise.resolve(true)
      // there's no way to refresh tokens, we can only create new ones,
      // so to avoid having a bunch of old tokens laying around, we'll
      // need to clean up the old ones first
      return axios.get(TOKEN_URL)
        .then(response => {
          for (const token of response.data) {
            // delete old tokens used by us
            if (token.name == TOKEN_KEY) {
              context.dispatch('deleteToken', token.id)
            }
          }
          // now we can create a new token
          context.dispatch('createToken')
          return response
        })
        .catch(response => {
          Vue.notify({'title': 'Failed to retrieve existing API tokens',
            'type': 'error'})
          return Promise.reject(response)
        })
    },
    // Deleting the token is as good as signing out as all API calls will
    // no longer be authenticated
    signOut(context) {
      localStorage.removeItem(TOKEN_KEY)
      if (context) context.commit('token', '')
      delete axios.defaults.headers.common["Authorization"]
    }
  }
}

// make sure we're authed on page load if there's a stored token
if (localStorage.getItem(TOKEN_KEY))
  axios.defaults.headers.common['Authorization'] = "Bearer " + localStorage.getItem(TOKEN_KEY)

