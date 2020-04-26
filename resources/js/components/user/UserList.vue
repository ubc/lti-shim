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
            <button type='button' class='btn btn-outline-danger'>
              <DeleteIcon /> Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>
	</div>
</template>

<script>
import EditIcon from 'icons/Pencil'
import DeleteIcon from 'icons/Delete'

export default {
	name: "UserList",
  components: {
    EditIcon,
    DeleteIcon
  },
  computed: {
    users() { return this.$store.state.user.users }
  },
  mounted() {
    this.$store.dispatch('user/getUsers')
  }
}
</script>

<style scoped>
</style>
