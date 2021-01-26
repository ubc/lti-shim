import axios from 'axios'
import Vue from 'vue'

// calls Laravel Fortify provided methods for authentication, also will
// initialize the CSRF token

const USERS_URL = '/api/midway/users/'

const lookup = {
  namespaced: true,
  state: {
    platformName: '',
    toolName: '',
    toolId: null,
    courseContextId: null,
    users: []
  },
  mutations: {
    setPlatformName(state, value) {
      state.platformName = value
    },
    setCourseContextId(state, value) {
      state.courseContextId = value
    },
    setToolName(state, value) {
      state.toolName = value
    },
    setToolId(state, value) {
      state.toolId = value
    },
    setCourseContextId(state, value) {
      state.courseContextId = value
    },
    setUsers(state, value) {
      state.users = value
    }
  },
  getters: {
  },
  actions: {
    getUsers(context) {
      let url = USERS_URL + 'course_context/' + context.state.courseContextId +
        '/tool/' + context.state.toolId
      // axios should automatically set the CSRF header from the returned
      // values
      return axios.get(url)
        .then(response => {
          context.commit('setUsers', response.data)
          return response
        })
        .catch(response => {
          // TODO: show error
          //Vue.notify({'title': 'Failed to establish CSRF protection',
          //            'type': 'error'})
          return Promise.reject(response)
        })
    }
  }
}

export default lookup
