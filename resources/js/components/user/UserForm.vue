<template>
	<div>
		<h3 v-if='isEdit'>Edit User</h3>
		<h3 v-else>Add User</h3>
    <form @submit.prevent='save'>
      <div class='form-group'>
        <label for='name'>Name</label>
        <input id='name' type='text' class='form-control' required
          :disabled='!user.name && isEdit'
          v-model='user.name' />
      </div>
      <div class='form-group'>
        <label for='email'>Email</label>
        <input id='email' type='email' class='form-control' required
          :disabled='!user.email && isEdit'
          v-model='user.email'/>
      </div>
      <PasswordField :isNewPassword='true' :isRequired='!isEdit && !isEditSelf' 
        v-if='!isEdit || isEditSelf'
        v-model='user.password'></PasswordField>
      <button type='submit' class='btn btn-outline-primary'
        :disabled='isWaiting'>
        <span class="spinner-border spinner-border-sm" role="status"
          aria-hidden="true" v-if='isWaiting'></span>
        <SaveIcon v-else />
        Save
      </button>
      <button type='button' class='btn btn-outline-secondary'
        @click="$emit('done')" :disabled='isWaiting'>
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
    },
    isEditSelf: {
      type: Boolean,
      default: false
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
    },
    isWaiting: false
  }},
  methods: {
    save() {
      let action = 'user/create'
      if (this.isEdit) action = 'user/update'

      this.isWaiting = true
      this.$store.dispatch(action, this.user)
        .then(() => {
          this.$emit('done')
          this.isWaiting = false
        })
        .catch(() => {
          this.isWaiting = false
        })
    }
  },
  mounted() {
    if (this.isEdit) {
      // I don't want changes here to be picked up by the store until the
      // user has pressed the save button. So we need to clone the user data to
      // a new object that's not managed by vuex.
      this.user = Object.assign({}, this.$store.state.user.users[this.userId])
      // in case we don't have the user in the store, try to grab just
      // that user id
      if (!('id' in this.user)) {
        this.isWaiting = true
        this.$store.dispatch('user/get', this.userId)
          .then(() => {
            this.user =
              Object.assign({}, this.$store.state.user.users[this.userId])
            this.isWaiting = false
          })
      }
    }
  }
}
</script>

<style scoped>
</style>
