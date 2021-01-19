import Vue from 'vue'

import helper from './helper'
import moduleBase from './moduleBase'

const apiUrl = '/api/platform'

// custom vuex implementation that we add to the base module
const state = {}
const getters = {}
const mutations = {}
const actions = {
  deleteClient(context, params) {
    let path = apiUrl + '/' + params.platformId + '/clients/' + params.clientId
    return axios.delete(path)
      .then(response => {
        context.dispatch('get', params.platformId)
        return response
      })
      .catch(error => {
        return helper.processError(error,
          {title: 'Failed to delete platform client ' + params.clientId})
      })
  },
  deleteKey(context, params) {
    let path = apiUrl + '/' + params.platformId + '/keys/' + params.keyId
    return axios.delete(path)
      .then(response => {
        context.dispatch('get', params.platformId)
        return response
      })
      .catch(error => {
        return helper.processError(error,
          {title: 'Failed to delete platform key ' + params.keyId})
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
const platform = moduleBase('platform', apiUrl, extensions)

export default platform
