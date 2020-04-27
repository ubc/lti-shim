<template>
	<div>
    <Notification />
    <UserForm :userId='Number(userId)' :isEditSelf='true' v-if='isSignedIn'>
    </UserForm>
	</div>
</template>

<script>
import Notification from './util/Notification'
import UserForm from './user/UserForm'

export default {
	name: "Account",
  props: ['userId'],
  components: {
    Notification,
    UserForm
  },
  computed: {
		isSignedIn() {
			return this.$store.getters['auth/isSignedIn']
		},
  },
  mounted() {
    this.$store.dispatch('auth/signIn')
  }
}
</script>

<style scoped>
</style>
