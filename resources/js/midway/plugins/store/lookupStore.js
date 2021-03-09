import axios from 'axios'
import Vue from 'vue'

const USERS_URL = '/api/midway/users/'

const lookup = {
  namespaced: true,
  state: {
    platformName: '',
    toolName: '',
    toolId: null,
    courseContextId: null,
    users: [],
    totalUsers: 0
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
    },
    setTotalUsers(state, value) {
      state.totalUsers = value
    }
  },
  getters: {
  },
  actions: {
    getUsers(context, params) {
      let url = USERS_URL + 'course_context/' + context.state.courseContextId +
        '/tool/' + context.state.toolId
      // encode the params in the URL query.
      return axios.get(url, {'params': params})
        .then(response => {
          context.commit('setUsers', response.data.data)
          context.commit('setTotalUsers', response.data.total)
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
