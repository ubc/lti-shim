import Vue from 'vue'

import helper from './helper'
import moduleBase from './moduleBase'

const apiUrl = '/api/user'

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
const user = moduleBase('user', apiUrl, extensions)

export default user
