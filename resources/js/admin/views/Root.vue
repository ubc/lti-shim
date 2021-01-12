<template>
  <div>
    <navbar :app-name='appName' />
    <div class='container py-4'>
      <router-view></router-view>
    </div>
  </div>
</template>

<script>
import Navbar from '../components/Navbar'

export default {
  name: "Root",
  components: {
    Navbar
  },
  props: ['appName']
}
</script>

<style scoped>
</style>
