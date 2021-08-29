<template>
  <div>
    <h1>Admin Home</h1>
    <LtiDictionary />

    <ShimConfigInfo></ShimConfigInfo>
  </div>
</template>

<script>
import LtiDictionary from '@admin/components/help/LtiDictionary'
import ShimConfigInfo from '@admin/components/help/ShimConfigInfo'

export default {
  name: "AdminHomeView",
  components: {
    LtiDictionary,
    ShimConfigInfo
  }
}
</script>

<style scoped>
</style>
