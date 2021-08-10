<template>
  <div>
    <Banner />
    <div class='container mx-auto p-4'>
      <p>
      The <strong>LTI Shim</strong> provides access to various external tools in
      a way that protects private information. Users can choose to what extent
      they wish to share their private information with external tools. Only
      external tools that support LTI 1.3 are supported.
      </p>
      <p class='notifyInfo mt-4'>
      Instructors and students should access this tool through Canvas.
      </p>
    </div>
  </div>
</template>

<script>
import Banner from '../components/Banner'

export default {
  name: "Splash",
  components: {
    Banner
  }
}
</script>

<style scoped>
</style>
