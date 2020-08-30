<template>
  <div>
    <p>
    Select a fake user below to get the real user information.
    </p>

    <!-- Fake user table -->
    <vue-good-table :columns='columns' :rows='users' :line-numbers='true'
         :search-options="{
                           enabled: true,
                           placeholder: 'Search fake users'
                           }"
         :sort-options="{
                        enabled: true,
                        initialSortBy: {field: 'name', type: 'asc'}
                        }"
         @on-row-click='showRealUser'
         v-show='!showUserInfo'
      >
      <div slot='emptystate'>
        No users have been enroled in the course yet.
      </div>
    </vue-good-table>

    <!-- Real user info box -->
    <div class='card mt-3' v-show='showUserInfo'>
      <div class='card-body'>
        <table class='table table-striped table-borderless'>
          <thead>
            <tr>
              <th scope='col'></th>
              <th scope='col'>Name</th>
              <th scope='col'>Student Number</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <th scope='row'>Fake</th>
              <td>{{ userInfo.fakeName }}</td>
              <td>{{ userInfo.fakeStudentNumber }}</td>
            </tr>
            <tr>
              <th scope='row'>Real</th>
              <td>{{ userInfo.realName }}</td>
              <td>{{ userInfo.realStudentNumber }}</td>
            </tr>
          </tbody>
        </table>
        <button type='button' class='btn btn-secondary' @click='hideRealUser'>
          <BackIcon /> Back
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import BackIcon from 'icons/ArrowLeft'

import { VueGoodTable } from 'vue-good-table'

export default {
  name: "UserList",
  components: {
    BackIcon,
    VueGoodTable
  },
  props: {
    users: {
      type: Array,
      required: true
    }
  },
  data() { return {
    columns: [
      {
        label: 'Name',
        field: 'name'
      },
      {
        label: 'StudentNumber',
        field: 'student_number'
      }
    ],
    userInfo: {
      fakeName: '',
      fakeStudentNumber: '',
      realName: '',
      realStudentNumber: ''
    },
    showUserInfo: false
  }},
  methods: {
    showRealUser(params) {
      this.userInfo.fakeName = params.row.name
      this.userInfo.fakeStudentNumber = params.row.student_number
      this.userInfo.realName =  params.row.lti_real_user.name
      this.userInfo.realStudentNumber = params.row.lti_real_user.student_number
      this.showUserInfo = true
    },
    hideRealUser() {
      this.showUserInfo = false
    }
  },
  mounted() {
  }
}
</script>

<style scoped>
</style>
