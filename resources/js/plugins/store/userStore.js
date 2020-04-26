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
    deleteUser(state, userId) {
      Vue.delete(state.users, userId)
    }
  },

  actions: {
    // get a list of users
    getAll(context) {
      axios.get(USER_API_URL)
        .then(response => {
          context.commit('setUsers', response.data)
        })
        .catch(response => {
          console.log("Unable to retrieve users.")
          console.log(response.data)
        })
    },
    // create a new user
    create(context, user) {
      axios.put(USER_API_URL, user)
        .then(response => {
          context.commit('addUser', response.data)
        })
        .catch(response => {
          console.log("Unable to create new user.")
          console.log(response.data)
        })
    },
    // delete an existing user
    delete(context, userId) {
      if (!(userId in context.state.users)) {
        console.log('Trying to delete unknown user id ' + userId)
      }
      axios.delete(USER_API_URL + '/' + userId)
        .then(response => {
          context.commit('deleteUser', userId)
        })
        .catch(response => {
          console.log('Unable to delete user id ' + userId)
          console.log(response.data)
        })
    }
  }
}
