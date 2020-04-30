import Vue from 'vue'

import helper from './helper'
import moduleBase from './moduleBase'

const apiUrl = '/api/tool'

// custom vuex implementation that we add to the base module
const state = {}
const getters = {}
const mutations = {}
const actions = {
  deleteKey(context, params) {
    let path = apiUrl + '/' + params.toolId + '/keys/' + params.keyId
    return axios.delete(path)
      .then(response => {
        context.dispatch('get', params.toolId)
        return response
      })
      .catch(error => {
        return helper.processError(error,
          {title: 'Failed to delete tool key ' + params.keyId})
      })
  },
}

// gather together the customization to pass to the base module
const extensions = {
  state: state,
  getters: getters,
  mutations: mutations,
  actions: actions
}
// create the new module
const tool = moduleBase('tool', apiUrl, extensions)

export default tool
