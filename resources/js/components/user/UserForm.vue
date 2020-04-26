<template>
	<div>
		<h3>Add User</h3>
    <form @submit.prevent='save'>
      <div class='form-group'>
        <label for='name'>Name</label>
        <input id='name' type='text' class='form-control' required
          v-model='user.name' />
      </div>
      <div class='form-group'>
        <label for='email'>Email</label>
        <input id='email' type='email' class='form-control' required
          v-model='user.email'/>
      </div>
      <PasswordField :isNewPassword='true' :isRequired='true'
        v-model='user.password'></PasswordField>
      <button type='submit' class='btn btn-outline-primary'>
        <SaveIcon />
        Save
      </button>
      <button type='button' class='btn btn-outline-secondary'
        @click="$emit('done')">
        <CancelIcon />
        Cancel
      </button>
    </form>
	</div>
</template>

<script>
import CancelIcon from 'icons/Cancel'
import SaveIcon from 'icons/ContentSave'

import PasswordField from './PasswordField'

export default {
	name: 'UserForm',
  components: {
    CancelIcon,
    PasswordField,
    SaveIcon,
  },
  data() { return {
    user: {
      name: '',
      email: '',
      password: ''
    }
  }},
  methods: {
    save() {
      this.$store.dispatch('user/createUser', this.user)
      this.$emit('done')
    }
  }
}
</script>

<style scoped>
</style>
