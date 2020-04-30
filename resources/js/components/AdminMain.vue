<template>
	<div>
    <Notification />
    <h1>Admin</h1>
    <div v-if='isSignedIn'>
      <h2>Tools</h2>
      <PlatformAdmin class='mb-4'></PlatformAdmin>
      <UserAdmin></UserAdmin>
    </div>
    <div v-else>
      Please Wait...
    </div>
	</div>
</template>

<script>
import Notification from './util/Notification'
import PlatformAdmin from './platform/PlatformAdmin'
import UserAdmin from './user/UserAdmin'

export default {
	name: "AdminMain",
  components: {
    Notification,
    PlatformAdmin,
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
