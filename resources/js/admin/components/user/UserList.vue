<template>
	<div>
    <table class="plainTable">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Name</th>
          <th scope="col">Email</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for='user in users'>
          <th scope="row">{{ user.id }}</th>
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td class='flex justify-between gap-2'>
            <router-link class='btnSecondary' tag='button'
                         :to="{name: 'editUser', params: {userId: user.id}}">
              <EditIcon /> Edit
            </router-link>
            <AreYouSureButton v-if='Object.keys(users).length > 1'
              :css="'btnDanger'"
              :warning="'Delete user ' + user.name + '?'"
              @yes='deleteUser(user.id)'>
                <DeleteIcon /> Delete
            </AreYouSureButton>
          </td>
        </tr>
      </tbody>
    </table>
	</div>
</template>

<script>
import DeleteIcon from 'icons/Delete'
import EditIcon from 'icons/Pencil'

import AreYouSureButton from '../util/AreYouSureButton'

export default {
	name: "UserList",
  components: {
    AreYouSureButton,
    DeleteIcon,
    EditIcon,
  },
  computed: {
    users() { return this.$store.state.user.items }
  },
  methods: {
    deleteUser(userId) {
      this.$store.dispatch('user/delete', userId)
    }
  },
  mounted() {
    // to avoid multiple requests, don't reget the user list unless it's empty
    if (Object.keys(this.users).length === 0)
      this.$store.dispatch('user/getAll')
  }
}
</script>

<style scoped>
</style>
