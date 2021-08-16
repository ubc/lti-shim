<template>
  <div class='container mx-auto flex flex-col md:flex-row md:gap-4
    '>
    <AdminNav class='flex-none' />
    <div class='p-2'>
      <router-view :key="$route.path"></router-view>
      <!--
        TODO: make PlatformClient accessible again
      <PlatformClientAdmin class='section'></PlatformClientAdmin>
      -->
    </div>
  </div>
</template>

<script>
import PlatformClientAdmin from '@admin/components/platformClient/PlatformClientAdmin'
import AdminNav from '@admin/components/AdminNav'

export default {
  name: "AdminMain",
  components: {
    PlatformClientAdmin,
    AdminNav
  },
  computed: {
  },
  mounted() {
  }
}
</script>

<style scoped>
.section {
  @apply border-t border-b border-gray-300 mb-4;
}
</style>
