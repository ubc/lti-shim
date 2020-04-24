import Vue from 'vue'
import Vuex from 'vuex'

import { auth } from './authStore'
import { user } from './userStore'

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store({
  modules: {
    auth,
    user,
  },
  strict: debug
})
