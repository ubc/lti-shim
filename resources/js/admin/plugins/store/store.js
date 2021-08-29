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
    appName: '',
    // used by ShimConfigInfo, don't really want to add a new module for them
    selectedPlatformId: 0,
    selectedToolId: 0,
  },
  mutations: {
    setAppName(state, appName) {
      state.appName = appName
    },
    setSelectedPlatformId(state, platformId) {
      state.selectedPlatformId = platformId
    },
    setSelectedToolId(state, toolId) {
      state.selectedToolId = toolId
    },
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
