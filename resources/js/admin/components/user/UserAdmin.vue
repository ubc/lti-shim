<template>
  <div>
    <h2>Users</h2>

    <UserForm v-if='showUserForm' :userId='editUser'
      @done='showUserForm = false'></UserForm>
    <div v-else>
      <p>
      Admin users only. LTI users are not managed here.
      </p>
      <button type='button' class='btnPrimary mb-4'
        @click='add'>
        <AddIcon /> Add User
      </button>
      <UserList @edit='edit'></UserList>
    </div>

  </div>
</template>

<script>
import AddIcon from 'icons/Plus'

import UserList from './UserList'
import UserForm from './UserForm'

export default {
  name: "UserAdmin",
  components: {
    AddIcon,
    UserForm,
    UserList
  },
  computed: {
  },
  data() { return {
    showUserForm: false,
    editUser: 0
  }},
  methods: {
    add() {
      this.editUser = 0
      this.showUserForm = true
    },
    edit(userId) {
      this.editUser = userId
      this.showUserForm = true
    }
  },
  mounted() {
  }
};
</script>

<style scoped>
</style>
