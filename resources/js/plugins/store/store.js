import Vue from 'vue'
import Vuex from 'vuex'

import { auth } from './authStore'
import { user } from './userStore'
import platform from './platformStore'


Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store({
  modules: {
    auth,
    platform,
    user,
  },
  strict: debug
})
