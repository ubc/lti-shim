import Vue from 'vue'

const USER_API_URL = 'api/user'

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
    editUser(state, user) {
      state.users[user.id] = user
    },
    deleteUser(state, userId) {
      Vue.delete(state.users, userId)
    },
  },

  actions: {
    // get a list of users
    getAll(context) {
      axios.get(USER_API_URL)
        .then(response => {
          context.commit('setUsers', response.data)
        })
        .catch(response => {
          Vue.notify({title: "Failed to get users", type: 'error'})
        })
    },
    // get a single user
    get(context, userId) {
      axios.get(USER_API_URL + '/' + userId)
        .then(response => {
          context.commit('addUser', response.data)
        })
        .catch(response => {
          Vue.notify({title: "Failed to get user " + userId, type: 'error'})
        })
    },
    // create a new user
    create(context, user) {
      return axios.put(USER_API_URL, user)
        .then(response => {
          context.commit('addUser', response.data)
          Vue.notify({title: "Added new user " + user.name, type: 'success'})
        })
        .catch(response => {
          Vue.notify({title: "Failed to add new user", type: 'error'})
        })
    },
    // update an existing user
    update(context, user) {
      return axios.post(USER_API_URL + '/' + user.id, user)
        .then(response => {
          Vue.notify({title: "Edited user " + user.name, type: 'success'})
          context.commit('editUser', response.data)
        })
        .catch(response => {
          Vue.notify({title: "Failed to edit user " + user.id, type: 'error'})
        })
    },
    // delete an existing user
    delete(context, userId) {
      if (!(userId in context.state.users)) {
        Vue.notify({title: "Can't delete unknown user "+user.id, type: 'error'})
        return
      }
      let name = context.state.users[userId].name
      axios.delete(USER_API_URL + '/' + userId)
        .then(response => {
          Vue.notify({title: "Deleted user " + name, type: 'success'})
          context.commit('deleteUser', userId)
        })
        .catch(response => {
          Vue.notify({title: "Failed to delete user " + user.id, type: 'error'})
        })
    }
  }
}
