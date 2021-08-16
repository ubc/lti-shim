import Vue from 'vue'

import helper from './helper'

// the moduleBase unfortunately doesn't work with a nested resource like
// platformClient, where we have api urls like
// /api/platform/{platformId}/client/{platformClientId}
// we basically need to know the platformId for every call. I'm not sure how
// to abstract this out yet, but since this is so far the only instance
// of a nested resource in the admin app, we'll forgo the abstraction and
// just have a full implementation here for now

const itemType = 'allowed tool'

const platformClient = {
  namespaced: true,

  state: {
    items: {},
  },

  getters: {
    getCopy: (state) => (itemId) => {
      return JSON.parse(JSON.stringify(state.items[itemId]))
    },
    hasItem: (state) => (itemId) => {
      return (itemId in state.items)
    },
    getApiUrl: (state) => (apiUrlParams) => {
      let url = '/api/platform/' + apiUrlParams['platformId'] + '/client'
      let itemId = -1;
      if ('item' in apiUrlParams)
        itemId = apiUrlParams['item'].id
      if ('itemId' in apiUrlParams)
        itemId = apiUrlParams['itemId']
      if (itemId > 0)
        url = url + '/' + itemId
      return url
    },
    getByPlatform: (state) => (platformId) => {
      let ret = []
      for (const itemId in state.items) {
        if (state.items[itemId].platform_id == platformId)
          ret.push(state.items[itemId])
      }
      return ret
    }
  },

  mutations: {
    addItems(state, items) {
      for (const item of items) {
        // since we're adding new properties to an existing object, we need
        // to explicitly tell Vue to make the property reactive with Vue.set
        Vue.set(state.items, item.id, item)
      }
    },
    addItem(state, item) {
      Vue.set(state.items, item.id, item)
    },
    deleteItem(state, itemId) {
      Vue.delete(state.items, itemId)
    },
  },

  actions: {
    // get a list of items
    getAll(context, params) {
      let apiUrl = context.getters['getApiUrl'](params)
      return axios.get(apiUrl)
        .then(response => {
          context.commit('addItems', response.data)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: 'Failed to get ' + itemType})
        })
    },
    // get a single item
    get(context, params) {
      let apiUrl = context.getters['getApiUrl'](params)
      let itemId = params['itemId']
      return axios.get(apiUrl)
        .then(response => {
          context.commit('addItem', response.data)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: 'Failed to get ' + itemType + ' ' + itemId})
        })
    },
    getCopy(context, params) {
      let itemId = params['itemId']
      if (context.getters['hasItem'](itemId)) {
        return new Promise((resolve, reject) => {
          resolve(context.getters['getCopy'](itemId))
        })
      }
      return context.dispatch('get', params)
        .then(response => {
          return context.getters['getCopy'](itemId)
        })
    },
    // create a new item
    add(context, params) {
      let apiUrl = context.getters['getApiUrl'](params)
      let item = params['item']
      return axios.post(apiUrl, item)
        .then(response => {
          context.commit('addItem', response.data)
          Vue.notify({title: 'Added new ' + itemType,
            type: 'success'})
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: 'Failed to add new ' + itemType})
        })
    },
    // update an existing item
    edit(context, params) {
      let apiUrl = context.getters['getApiUrl'](params)
      let item = params['item']
      return axios.patch(apiUrl, item)
        .then(response => {
          Vue.notify({title: 'Edited ' + itemType,
            type: 'success'})
          context.commit('addItem', response.data)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: 'Failed to edit ' + itemType + ' ' + item.id})
        })
    },
    // delete an existing item
    delete(context, params) {
      let apiUrl = context.getters['getApiUrl'](params)
      let itemId = params['itemId']
      if (!(itemId in context.state.items)) {
        Vue.notify({title: "Can't delete unknown " + itemType + ' ' +item.id})
        return Promise.resolve(true)
      }
      return axios.delete(apiUrl)
        .then(response => {
          Vue.notify({title: 'Deleted ' + itemType,
            type: 'success'})
          context.commit('deleteItem', itemId)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: 'Failed to delete ' + itemType + ' ' + item.id})
        })
    },
  }
}

export default platformClient
