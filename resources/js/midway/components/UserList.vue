<template>
  <div>
    <!-- Fake user table -->
    <div class='card' v-show='!showUserInfo'>
      <div class='card-body'>
        <h5>
        Search for Identities:
        </h5>
        <!-- Toggle between Tool (fake users) and Platform (real users) -->
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="searchTargetTool" name="searchTarget"
            v-model='serverParams.showToolUsers' :value='true'
            class="custom-control-input" @change='onToolPlatformToggle'>
          <label for="searchTargetTool" class="custom-control-label">
            in {{ tool }}</label>
        </div>
        <div class="custom-control custom-radio custom-control-inline">
          <input type="radio" id="searchTargetPlatform" name="searchTarget"
            v-model='serverParams.showToolUsers' :value='false'
            class="custom-control-input" @change='onToolPlatformToggle'>
          <label for="searchTargetPlatform" class="custom-control-label">
            in {{ platform }}</label>
        </div>
        <!-- Search box  -->
        <form class='form mt-4' v-on:submit.prevent="onSearch">
          <div class='d-flex'>
            <label for="search" class='flex-shrink-1 mr-1'>
              <SearchIcon title='Search' class='h2 text-secondary' />
            </label>
            <input type="text" name="search" id="search" v-model='search'
              class='form-control flex-grow-1 mr-2'
              :placeholder='searchPlaceholder' />
            <div class='flex-shrink-1'>
              <button type='submit' class='btn btn-secondary'>Search</button>
            </div>
          </div>
        </form>
        <!-- Search indicator -->
        <div v-if='serverParams.search' class='text-muted'>
          <small>
          Searching for "{{ serverParams.search }}"
          </small>
        </div>
        <!-- Users table -->
        <vue-good-table mode='remote'
                        @on-page-change='onPageChange'
                        @on-sort-change='onSortChange'
                        @on-per-page-change='onPerPageChange'
                        @on-row-click='showRealUser'
                        :isLoading.sync='isLoading'
                        :pagination-options='paginationOptions'
                        :search-options='{enabled: true, externalQuery: search}'
                        :totalRows="totalUsers"
                        :columns='columns'
                        :rows='usersComputed' />
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
              <th scope='col'>Name in {{ userInfo.appName }}</th>
              <th scope='col'>Student Number in {{ userInfo.appName }}</th>
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
import SearchIcon from 'icons/Magnify'

import { VueGoodTable } from 'vue-good-table'

export default {
  name: "UserList",
  components: {
    BackIcon,
    SearchIcon,
    VueGoodTable
  },
  computed: {
    columns() {
      if (this.serverParams.showToolUsers) {
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
          label: 'Name in ' + this.platform,
          field: 'lti_real_user.name'
        },
        {
          label: 'Student Number in ' + this.platform,
          field: 'lti_real_user.student_number'
        }
      ]
    },
    platform() { return this.$store.state.lookup.platformName },
    searchPlaceholder() {
      if (this.serverParams.showToolUsers)
        return 'Search identities in ' + this.tool
      return 'Search identities in ' + this.platform
    },
    tool() { return this.$store.state.lookup.toolName },
    users() { return this.$store.state.lookup.users },
    totalUsers() { return this.$store.state.lookup.totalUsers },
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
    isLoading: false,
    paginationOptions: {
      enabled: true,
      dropdownAllowAll: false
    },
    showUserInfo: false,
    // since the user list could be huge (thousands of users), we don't want to
    // rely on the client side for paging/sort/filtering. So have to tell the
    // server  about paging/sort/filter params.
    serverParams: {
      showToolUsers: true,
      search: '',
      sortType: '',
      sortField: '',
      page: 1,
      perPage: 10
    },
    search: '', // NOT a dup of serverParams.search. This holds the value of the
                // search input box, which is updated on every keypress. The one
                // in serverParams only gets updated when we submit the search.
    userInfo: {
      appName: '',
      selectedName: '',
      selectedStudentNumber: '',
      revealedName: '',
      revealedStudentNumber: ''
    },
  }},
  methods: {
    showRealUser(params) {
      if (this.serverParams.showToolUsers) {
        this.userInfo.appName = this.platform
        this.userInfo.selectedName = params.row.name
        this.userInfo.selectedStudentNumber = params.row.student_number
        this.userInfo.revealedName = params.row.lti_real_user.name
        this.userInfo.revealedStudentNumber =
          params.row.lti_real_user.student_number
      }
      else {
        this.userInfo.appName = this.tool
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
    },

    updateServerParams(params) {
      this.serverParams = Object.assign({}, this.serverParams, params)
    },

    onPageChange(params) {
      this.updateServerParams({page: params.currentPage})
      this.loadItems()
    },
    onPerPageChange(params) {
      this.updateServerParams({perPage: params.currentPerPage})
      this.loadItems()
    },
    onSortChange(params) {
      this.updateServerParams({
        sortType: params[0].type,
        sortField: params[0].field
      })
      this.loadItems()
    },
    onSearch(params) {
      this.updateServerParams({search: this.search})
      this.loadItems()
    },
    onToolPlatformToggle() {
      this.isLoading = true
      // need to reset sort as API will error out if we try to sort by real
      // user fields when on the tool side
      this.updateServerParams({
        sortType: '',
        sortField: ''
      })
      this.loadItems()
    },
    loadItems() {
      this.$store.dispatch('lookup/getUsers', this.serverParams)
        .finally(() => { this.isLoading = false })
    }
  },
  mounted() {
    this.loadItems()
  }
}
</script>

<style scoped>
.theadVueGoodTable {
 color: #606266;
 background: linear-gradient(#f4f5f8,#f1f3f6);
}
</style>
