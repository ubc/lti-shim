<template>
	<div>
    <table class="table table-hover">
      <thead class="thead-light">
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
          <td class='d-flex justify-content-between'>
            <button type='button' class='btn btn-outline-secondary'>
              <EditIcon /> Edit
            </button>
            <AreYouSureButton v-if='Object.keys(users).length > 1'
              :css="'btn btn-outline-danger'"
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
    users() { return this.$store.state.user.users }
  },
  methods: {
    deleteUser(userId) {
      this.$store.dispatch('user/delete', userId)
    }
  },
  mounted() {
    this.$store.dispatch('user/getAll')
  }
}
</script>

<style scoped>
</style>
