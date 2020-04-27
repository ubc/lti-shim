import Vue from 'vue'
import axios from 'axios'
import { auth } from './authStore'

export default {
  // helper for axios request promises' catch block.
  // convert validation error from laravel into a list for display to
  // the user, show error notification, and return a rejected promise
  processError(error, notification) {
    // session expired, so we have to force logout
    if (error.response.status == 401) {
      auth.actions.signOut(false)
      axios.post('/logout')
        .then(response => {
          // refreshing the page should take the user to login
          location.reload()
        })
      return Promise.reject(error)
    }
    // show error to user
    let msg = ''
    if (error.response.data.errors) {
      let errorsList = '<ul>'
      for (const field in error.response.data.errors) {
        errorsList += '<li>' + field + '<ul>'
        for (const errMsg of error.response.data.errors[field]) {
          errorsList += '<li>' + errMsg + '</li>'
        }
        errorsList += '</ul></li>'
      }
      errorsList += '</ul>'
      msg += errorsList
    }
    if (msg) {
      notification['text'] = msg
      notification['duration'] = -1
    }
    notification['type'] = 'error'
    Vue.notify(notification)
    return Promise.reject(error)
  }
}
