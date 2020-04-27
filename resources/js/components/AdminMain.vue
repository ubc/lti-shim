<template>
	<div>
    <Notification />
    <h1>Admin</h1>
    <div v-if='isSignedIn'>
      <h2>Tools</h2>
      <h2>Platforms</h2>
      <UserAdmin></UserAdmin>
    </div>
    <div v-else>
      Loading, Please Wait...
    </div>
	</div>
</template>

<script>
import Notification from './util/Notification'
import UserAdmin from './user/UserAdmin'

export default {
	name: "AdminMain",
  components: {
    Notification,
    UserAdmin,
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
