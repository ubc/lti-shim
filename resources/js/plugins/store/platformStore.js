import Vue from 'vue'

import moduleBase from './moduleBase'

const apiUrl = '/api/platform'

// custom vuex implementation that we add to the base module
const state = {}
const getters = {}
const mutations = {}
const actions = {}
// gather together the customization to pass to the base module
const extensions = {
  state: state,
  getters: getters,
  mutations: mutations,
  actions: actions
}
// create the new module
const platform = moduleBase('platform', apiUrl, extensions)

export default platform
