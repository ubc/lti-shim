<template>
  <div>
    <navbar :app-name='appName' />
    <div class='container py-4'>
      <Notification />
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import Navbar from '../components/Navbar'
import Notification from '../components/util/Notification'

export default {
  name: "Root",
  components: {
    Navbar,
    Notification
  },
  props: ['appName']
}
</script>

<style scoped>
</style>
