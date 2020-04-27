<template>
	<div>
		<h3 v-if='isEdit'>Edit User</h3>
		<h3 v-else>Add User</h3>
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
      <PasswordField :isNewPassword='true' :isRequired='true' v-if='!isEdit'
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
  props: {
    userId: {
      type: Number,
      default: 0
    }
  },
  computed: {
    isEdit() { return this.userId > 0 },
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
      if (this.isEdit)
        this.$store.dispatch('user/update', this.user)
      else
        this.$store.dispatch('user/create', this.user)
      this.$emit('done')
    }
  },
  mounted() {
    if (this.isEdit) {
      // I don't want changes here to be picked up by the store until the
      // user has pressed the save button. So we need to clone the user data to
      // a new object that's not managed by vuex.
      this.user = Object.assign({}, this.$store.state.user.users[this.userId])
    }
  }
}
</script>

<style scoped>
</style>
