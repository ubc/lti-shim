import Vue from 'vue'
import helper from './helper'

// attempt to abstract out a generic vuex store
// itemType - general name for the items being stored, e.g. user
// apiUrl - the REST API url for this item
// extensions - an object containing state/getters/mutations/actions to be added
//              to the module, this is how you can extend the base module
export default function(itemType, apiUrl, extensions) {
  const ACTION_GETALL = 'actionGetAll'
  const ACTION_GET = 'actionGet'

  return {
    namespaced: true,

    state: {
      items: {},
      // for debouncing requests, we store ongoing promises in waitingFor
      // and delete them once the promise completes. If we get a duplicate
      // action, we can just return the already ongoing promise.
      waitingFor: {},
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
      addWaitingFor(state, [actionName, actionPromise]) {
        Vue.set(state.waitingFor, actionName, actionPromise)
      },
      deleteWaitingFor(state, actionName) {
        Vue.delete(state.waitingFor, actionName)
      },
      ...extensions.mutations
    },

    actions: {
      // get a list of items
      getAll(context) {
        if (ACTION_GETALL in context.state.waitingFor)
          return context.state.waitingFor[ACTION_GETALL]

        let actionPromise = axios.get(apiUrl)
          .then(response => {
            context.commit('deleteWaitingFor', ACTION_GETALL)
            context.commit('addItems', response.data)
            return response
          })
          .catch(error => {
            context.commit('deleteWaitingFor', ACTION_GETALL)
            return helper.processError(error,
              {title: 'Failed to get ' + itemType})
          })

        context.commit('addWaitingFor', [ACTION_GETALL, actionPromise])
        return actionPromise
      },
      // get a single item
      get(context, itemId) {
        let actionName = ACTION_GET + itemId
        if (actionName in context.state.waitingFor)
          return context.state.waitingFor[actionName]

        let actionPromise = axios.get(apiUrl + '/' + itemId)
          .then(response => {
            context.commit('deleteWaitingFor', actionName)
            context.commit('addItem', response.data)
            return response
          })
          .catch(error => {
            context.commit('deleteWaitingFor', actionName)
            return helper.processError(error,
              {title: 'Failed to get ' + itemType + ' ' + itemId})
          })

        context.commit('addWaitingFor', [actionName, actionPromise])
        return actionPromise
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
