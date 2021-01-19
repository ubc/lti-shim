import Vue from 'vue'

import helper from './helper'
import moduleBase from './moduleBase'

const apiUrl = '/api/platform-client'

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
const platformClient = moduleBase('platform client', apiUrl, extensions)

export default platformClient
