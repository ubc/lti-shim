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
        <tr v-for='user in users' :key='user.id'>
          <th scope="row">{{ user.id }}</th>
          <td>{{ user.name }}</td>
          <td>{{ user.email }}</td>
          <td class='d-flex justify-content-between'>
            <button type='button' class='btn btn-outline-secondary'>
              Edit
            </button>
            <button type='button' class='btn btn-outline-danger'>
              Delete
            </button>
          </td>
        </tr>
      </tbody>
    </table>
	</div>
</template>

<script>
export default {
	name: "UserList",
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
