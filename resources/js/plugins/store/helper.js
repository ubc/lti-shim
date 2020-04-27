import Vue from 'vue'

export default {
  // helper for axios request promises' catch block.
  // convert validation error from laravel into a list for display to
  // the user, show error notification, and return a rejected promise
  processError(error, notification) {
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
    Vue.notify(notification)
    return Promise.reject(error)
  }
}
