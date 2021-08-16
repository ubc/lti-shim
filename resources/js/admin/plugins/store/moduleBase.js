import Vue from 'vue'
import helper from './helper'

// attempt to abstract out a generic vuex store
// itemType - general name for the items being stored, e.g. user
// apiUrl - the REST API url for this item
// extensions - an object containing state/getters/mutations/actions to be added
//              to the module, this is how you can extend the base module
export default function(itemType, apiUrl, extensions) {
  return {
    namespaced: true,

    state: {
      items: {},
      ...extensions.state
    },

    getters: {
      getCopy: (state) => (itemId) => {
        return JSON.parse(JSON.stringify(state.items[itemId]))
      },
      hasItem: (state) => (itemId) => {
        return (itemId in state.items)
      },
      ...extensions.getters
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
      ...extensions.mutations
    },

    actions: {
      // get a list of items
      getAll(context) {
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
      get(context, itemId) {
        return axios.get(apiUrl + '/' + itemId)
          .then(response => {
            context.commit('addItem', response.data)
            return response
          })
          .catch(error => {
            return helper.processError(error,
              {title: 'Failed to get ' + itemType + ' ' + itemId})
          })
      },
      getCopy(context, itemId) {
        if (context.getters['hasItem'](itemId)) {
          return new Promise((resolve, reject) => {
            resolve(context.getters['getCopy'](itemId))
          })
        }
        return context.dispatch('get', itemId)
          .then(response => {
            return context.getters['getCopy'](itemId)
          })
      },
      // create a new item
      add(context, item) {
        return axios.post(apiUrl, item)
          .then(response => {
            context.commit('addItem', response.data)
            Vue.notify({title: 'Added new ' + itemType + ' ' + item.name,
                        type: 'success'})
            return response
          })
          .catch(error => {
            return helper.processError(error,
              {title: 'Failed to add new ' + itemType})
          })
      },
      // update an existing item
      edit(context, item) {
        return axios.patch(apiUrl + '/' + item.id, item)
          .then(response => {
            Vue.notify({title: 'Edited ' + itemType + ' ' + item.name,
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
      delete(context, itemId) {
        if (!(itemId in context.state.items)) {
          Vue.notify({title: "Can't delete unknown " + itemType + ' ' +item.id})
          return Promise.resolve(true)
        }
        let name = context.state.items[itemId].name
        return axios.delete(apiUrl + '/' + itemId)
          .then(response => {
            Vue.notify({title: 'Deleted ' + itemType + ' ' + name,
                        type: 'success'})
            context.commit('deleteItem', itemId)
            return response
          })
          .catch(error => {
            return helper.processError(error,
              {title: 'Failed to delete ' + itemType + ' ' + item.id})
          })
      },
      ...extensions.actions
    }
  }
}
