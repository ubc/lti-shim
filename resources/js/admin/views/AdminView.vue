<template>
  <div>
    <h1 class='py-4'>Admin</h1>
    <PlatformAdmin class='section'></PlatformAdmin>
    <ToolAdmin class='section'></ToolAdmin>
    <PlatformClientAdmin class='section'></PlatformClientAdmin>
    <UserAdmin class='section'></UserAdmin>
    <ShimConfigInfo class='section'></ShimConfigInfo>
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
.section {
  @apply border border-gray-300 p-4 mb-4;
}
</style>
