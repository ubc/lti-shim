<template>
  <div>
    <Notification />
    <h1>Admin</h1>
    <div v-if='isSignedIn'>
      <PlatformAdmin class='mb-4'></PlatformAdmin>
      <ToolAdmin class='mb-4'></ToolAdmin>
      <PlatformClientAdmin class='mb-4'></PlatformClientAdmin>
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
import PlatformClientAdmin from './platformClient/PlatformClientAdmin'
import ShimConfigInfo from './help/ShimConfigInfo'
import ToolAdmin from './tool/ToolAdmin'
import UserAdmin from './user/UserAdmin'

export default {
  name: "AdminMain",
  components: {
    Notification,
    PlatformAdmin,
    PlatformClientAdmin,
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
