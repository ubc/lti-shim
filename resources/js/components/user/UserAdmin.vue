<template>
  <div>
    <h2>Users</h2>
    <UserForm v-if='showUserForm' @done='showUserForm = false'></UserForm>
    <div v-else>
      <button type='button' class='btn btn-outline-primary mb-3'
        @click='showUserForm = true'>
        <AddIcon /> Add User
      </button>
      <UserList></UserList>
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
    showUserForm: false
  }},
  methods: {
  },
  mounted() {
  }
};
</script>

<style scoped>
</style> 
