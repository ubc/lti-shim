<template>
  <div>
    <h2>Users</h2>

    <UserForm v-if='showUserForm' :userId='editUser'
              @done='$router.push({name: "adminUser"})'></UserForm>
    <div v-else>
      <p>
      Admin users only. LTI users are not managed here.
      </p>
      <router-link class='btnPrimary mb-2' tag='button'
                   :to="{name: 'addUser'}">
        <AddIcon /> Add User
      </router-link>
      <UserList></UserList>
    </div>

  </div>
</template>

<script>
import AddIcon from 'icons/Plus'

import UserList from '@admin/components/user/UserList'
import UserForm from '@admin/components/user/UserForm'

export default {
  name: "UserView",
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
  },
  mounted() {
    if (this.$route.name == 'editUser') {
      this.editUser = this.$route.params.userId
      this.showUserForm = true
    }
    else if (this.$route.name == 'addUser') {
      this.editUser = 0
      this.showUserForm = true
    }
  }
};
</script>

<style scoped>
</style>
