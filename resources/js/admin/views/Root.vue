<template>
  <div>
    <Notification />
    <router-view></router-view>
  </div>
</template>

<script>
import Notification from '../components/util/Notification'

export default {
  name: "Root",
  components: {
    Notification
  },
  props: ['appName'],
  mounted() {
    this.$store.commit('setAppName', this.appName)
  }
}
</script>

<style scoped>
</style>
