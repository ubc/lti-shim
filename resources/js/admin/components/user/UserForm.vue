<template>
	<div>
		<h3 v-if='isEdit'>Edit User</h3>
		<h3 v-else>Add User</h3>
    <form @submit.prevent='save' class='plainForm'>
      <label for='name'>Name</label>
      <input id='name' type='text' required
        :disabled='!user.name && isEdit'
        v-model='user.name' />

      <label for='email'>Email</label>
      <input id='email' type='email' required
        :disabled='!user.email && isEdit'
        v-model='user.email'/>

      <PasswordField :isNewPassword='true' :isRequired='!isEdit && !isEditSelf' 
        v-if='!isEdit || isEditSelf'
        v-model='user.password'></PasswordField>

      <div>
        <button type='submit' class='btnPrimary' :disabled='isWaiting'>
          <Spinner v-if='isWaiting' />
          <SaveIcon v-else />
          Save
        </button>
        <button type='button' class='btnSecondary' @click="$router.back()"
                :disabled='isWaiting'>
          <CancelIcon />
          Cancel
        </button>
      </div>
    </form>
	</div>
</template>

<script>
import CancelIcon from 'icons/Cancel'
import SaveIcon from 'icons/ContentSave'

import PasswordField from './PasswordField'
import Spinner from '../util/Spinner'

export default {
	name: 'UserForm',
  components: {
    CancelIcon,
    PasswordField,
    SaveIcon,
    Spinner,
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
      let action = 'user/add'
      if (this.isEdit) action = 'user/edit'

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
      this.$store.dispatch('user/getCopy', this.userId)
        .then((userCopy) => {
          this.user = userCopy
        })
    }
  }
}
</script>

<style scoped>
</style>
