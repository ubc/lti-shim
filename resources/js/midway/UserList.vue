<template>
  <div>
    <!-- Fake user table -->
    <div class='card' v-show='!showUserInfo'>
      <div class='card-body'>
        <h5>
        Search for Identities:
        </h5>
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="searchTargetTool" name="searchTarget"
            v-model='isSearchTargetTool' :value='true'
            class="custom-control-input">
          <label for="searchTargetTool" class="custom-control-label">
            in {{ tool }}</label>
        </div>
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="searchTargetPlatform" name="searchTarget"
            v-model='isSearchTargetTool' :value='false'
            class="custom-control-input">
          <label for="searchTargetPlatform" class="custom-control-label">
            in {{ platform }}</label>
        </div>

        <vue-good-table :columns='columns' :rows='usersComputed' class='mt-3'
             :sort-options='sortOptions' :search-options='searchOptions'
             @on-row-click='showRealUser'
          >
          <div slot='emptystate'>
            No users found!
          </div>
        </vue-good-table>
      </div>
    </div>

    <!-- Real user info box -->
    <div class='card mt-3' v-show='showUserInfo'>
      <div class='card-body'>
        <h5>
          {{ userInfo.selectedName }} ({{ userInfo.selectedStudentNumber }}) is:
        </h5>
        <table class='table table-bordered mt-3'>
          <thead class='theadVueGoodTable'>
            <tr>
              <th scope='col'>Name in {{ platform }}</th>
              <th scope='col'>Student Number in {{ platform }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ userInfo.revealedName }}</td>
              <td>{{ userInfo.revealedStudentNumber }}</td>
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
    platform: {
      type: String,
      required: true
    },
    tool: {
      type: String,
      required: true
    },
    users: {
      type: Array,
      required: true
    }
  },
  computed: {
    columns() {
      if (this.isSearchTargetTool) {
        return [
          {
            label: 'Name in ' + this.tool,
            field: 'name'
          },
          {
            label: 'Student Number in ' + this.tool,
            field: 'student_number'
          }
        ]
      }
      return [
        {
          label: 'Name in ' + this.tool,
          field: 'lti_real_user.name'
        },
        {
          label: 'Student Number in ' + this.tool,
          field: 'lti_real_user.student_number'
        }
      ]
    },
    sortOptions() {
      if (this.isSearchTargetTool) {
        return {
          enabled: true,
          initialSortBy: {field: 'name', type: 'asc'}
        }
      }
      return {
        enabled: true,
        initialSortBy: {field: 'lti_real_user.name', type: 'asc'}
      }
    },
    // some real users do not have student numbers, but vue-good-table will
    // complain if the student number column is missing, so we need to put an
    // empty string there to keep vue-good-table happy
    usersComputed() {
      let ret = []
      for (let i = 0; i < this.users.length; i++) {
        let user = this.users[i]
        if (!('student_number' in user.lti_real_user)) {
          user.lti_real_user.student_number = ''
        }
        ret.push(user)
      }
      return ret
    },
  },
  data() { return {
    isSearchTargetTool: true,
    searchOptions: {
      enabled: true,
      placeholder: 'Search identities in ' + this.tool
    },
    userInfo: {
      selectedName: '',
      selectedStudentNumber: '',
      revealedName: '',
      revealedStudentNumber: ''
    },
    showUserInfo: false
  }},
  methods: {
    showRealUser(params) {
      if (this.isSearchTargetTool) {
        this.userInfo.selectedName = params.row.name
        this.userInfo.selectedStudentNumber = params.row.student_number
        this.userInfo.revealedName = params.row.lti_real_user.name
        this.userInfo.revealedStudentNumber =
          params.row.lti_real_user.student_number
      }
      else {
        this.userInfo.selectedName = params.row.lti_real_user.name
        this.userInfo.selectedStudentNumber =
          params.row.lti_real_user.student_number
        this.userInfo.revealedName = params.row.name
        this.userInfo.revealedStudentNumber = params.row.student_number
      }
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
.theadVueGoodTable {
 color: #606266;
 background: linear-gradient(#f4f5f8,#f1f3f6);
}
</style>
