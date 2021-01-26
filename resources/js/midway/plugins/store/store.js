import Vue from 'vue'
import Vuex from 'vuex'

import lookup from './lookupStore'


Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production'

export default new Vuex.Store({
  modules: {
    lookup,
  },
  strict: debug
})
