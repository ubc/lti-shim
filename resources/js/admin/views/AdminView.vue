<template>
  <div>
    <h1>Admin</h1>
    <PlatformAdmin class='mb-4'></PlatformAdmin>
    <ToolAdmin class='mb-4'></ToolAdmin>
    <PlatformClientAdmin class='mb-4'></PlatformClientAdmin>
    <UserAdmin class='mb-4'></UserAdmin>
    <ShimConfigInfo></ShimConfigInfo>
  </div>
</template>

<script>
import Notification from '../components/util/Notification'
import PlatformAdmin from '../components/platform/PlatformAdmin'
import PlatformClientAdmin from '../components/platformClient/PlatformClientAdmin'
import ShimConfigInfo from '../components/help/ShimConfigInfo'
import ToolAdmin from '../components/tool/ToolAdmin'
import UserAdmin from '../components/user/UserAdmin'

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
  },
  mounted() {
  }
}
</script>

<style scoped>
</style>
