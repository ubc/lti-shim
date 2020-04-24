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
    }
  },

  actions: {
    getUsers(context){
      axios.get("api/user")
        .then(response => {
          console.log(response.data.users)
          context.commit('setUsers', response.data.users)
        })
        .catch(response =>{
          console.log("Unable to retrieve users.")
          console.log(response.data)
        })
    }
  }
}
