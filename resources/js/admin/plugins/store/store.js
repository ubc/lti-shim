import Vue from 'vue'
import Vuex from 'vuex'

import auth from './authStore'
import user from './userStore'
import platform from './platformStore'
import platformClient from './platformClientStore'
import tool from './toolStore'


Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store({
  state: {
    appName: ''
  },
  mutations: {
    setAppName(state, appName) {
      state.appName = appName
    }
  },
  modules: {
    auth,
    platform,
    platformClient,
    tool,
    user,
  },
  strict: debug
})
