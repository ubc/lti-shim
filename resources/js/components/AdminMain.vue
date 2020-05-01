<template>
	<div>
    <Notification />
    <h1>Admin</h1>
    <div v-if='isSignedIn'>
      <ToolAdmin class='mb-4'></ToolAdmin>
      <PlatformAdmin class='mb-4'></PlatformAdmin>
      <UserAdmin class='mb-4'></UserAdmin>
      <ShimConfigInfo></ShimConfigInfo>
    </div>
    <div v-else>
      Please Wait...
    </div>
	</div>
</template>

<script>
import Notification from './util/Notification'
import PlatformAdmin from './platform/PlatformAdmin'
import ShimConfigInfo from './help/ShimConfigInfo'
import ToolAdmin from './tool/ToolAdmin'
import UserAdmin from './user/UserAdmin'

export default {
	name: "AdminMain",
  components: {
    Notification,
    PlatformAdmin,
    ShimConfigInfo,
    ToolAdmin,
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
