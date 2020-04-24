<template>
	<div>
		<h3>Add User</h3>
    <form>
      <div class='form-group'>
        <label for='name'>Name</label>
        <input id='name' type='text' class='form-control' />
      </div>
      <div class='form-group'>
        <label for='email'>Email</label>
        <input id='email' type='email' class='form-control' />
      </div>
      <div class='form-group'>
        <label for='password'>Password</label>
        <input id='password' type='password' class='form-control' />
      </div>
      <button type='submit' class='btn btn-outline-primary'
        @click='save'>
        Save
      </button>
      <button type='button' class='btn btn-outline-secondary'
        @click="$emit('done')">
        Cancel
      </button>
    </form>
	</div>
</template>

<script>
export default {
	name: 'UserForm',
  methods: {
    save() {
      this.$emit('done')
    }
  }
}
</script>

<style scoped>
</style>
