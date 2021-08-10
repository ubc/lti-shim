<template>
  <div>
    <h1>Admin Home</h1>
    <ShimConfigInfo class='section'></ShimConfigInfo>
  </div>
</template>

<script>
import ShimConfigInfo from '@admin/components/help/ShimConfigInfo'

export default {
  name: "AdminHomeView",
  components: {
    ShimConfigInfo
  }
}
</script>

<style scoped>
</style>
