import Vue from 'vue'
import helper from './helper'

const USER_API_URL = '/api/user'

export const user = {
  namespaced: true,

  state: {
    users: {}
  },

  getters: {
  },

  mutations: {
    setUsers(state, users) {
      for (const user of users) {
        Vue.set(state.users, user.id, user)
      }
    },
    addUser(state, user) {
      Vue.set(state.users, user.id, user)
    },
    deleteUser(state, userId) {
      Vue.delete(state.users, userId)
    },
  },

  actions: {
    // get a list of users
    getAll(context) {
      return axios.get(USER_API_URL)
        .then(response => {
          context.commit('setUsers', response.data)
          return response
        })
        .catch(error => {
          return helper.processError(error, {title: "Failed to get users"})
        })
    },
    // get a single user
    get(context, userId) {
      return axios.get(USER_API_URL + '/' + userId)
        .then(response => {
          context.commit('addUser', response.data)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: "Failed to get user " + userId})
        })
    },
    // create a new user
    create(context, user) {
      return axios.put(USER_API_URL, user)
        .then(response => {
          context.commit('addUser', response.data)
          Vue.notify({title: "Added new user " + user.name, type: 'success'})
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: "Failed to add new user"})
        })
    },
    // update an existing user
    update(context, user) {
      return axios.post(USER_API_URL + '/' + user.id, user)
        .then(response => {
          Vue.notify({title: "Edited user " + user.name, type: 'success'})
          context.commit('addUser', response.data)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: "Failed to edit user " + user.id})
        })
    },
    // delete an existing user
    delete(context, userId) {
      if (!(userId in context.state.users)) {
        Vue.notify({title: "Can't delete unknown user "+user.id})
        return Promise.resolve(true)
      }
      let name = context.state.users[userId].name
      return axios.delete(USER_API_URL + '/' + userId)
        .then(response => {
          Vue.notify({title: "Deleted user " + name, type: 'success'})
          context.commit('deleteUser', userId)
          return response
        })
        .catch(error => {
          return helper.processError(error,
            {title: "Failed to delete user " + user.id})
        })
    }
  }
}
