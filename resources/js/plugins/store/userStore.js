const USER_API_URL = 'api/user'

export const user = {
  namespaced: true,

  state: {
    users: []
  },

  getters: {
  },

  mutations: {
    setUsers(state, data) {
      state.users = data
    },
    addUser(state, user) {
      state.users.push(user)
    }
  },

  actions: {
    // get a list of users
    getUsers(context) {
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
    createUser(context, user) {
      axios.put(USER_API_URL, user)
        .then(response => {
          context.commit('addUser', response.data)
        })
        .catch(response => {
          console.log("Unable to create new user.")
          console.log(response.data)
        })
    }
  }
}
