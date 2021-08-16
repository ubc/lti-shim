<template>
  <div>

    <h2>Platforms</h2>

    <div>
      <PlatformForm v-if='showForm' :platformId='editPlatformId'
        @done='showForm = false'/>
      <div v-else>
        <p>
        LTI platforms are usually Learning Management Systems (e.g.: Canvas),
        platforms send out LTI launches and information to tools.
        </p>

        <router-link class='btnPrimary mb-2' tag='button'
                     :to="{name: 'addPlatform'}">
          <AddIcon /> Add Platform
        </router-link>

        <PlatformList />
      </div>
    </div>

  </div>
</template>

<script>
import AddIcon from 'icons/Plus'

import PlatformForm from '@admin/components/platform/PlatformForm'
import PlatformList from '@admin/components/platform/PlatformList'

export default {
  name: "PlatformView",
  components: {
    AddIcon,
    PlatformForm,
    PlatformList,
  },
  data() { return {
    editPlatformId: 0,
    showForm: false
  }},
  methods: {
  },
  mounted() {
    if (this.$route.name == 'editPlatform') {
      this.editPlatformId = this.$route.params.platformId
      this.showForm = true
    }
    else if (this.$route.name == 'addPlatform') {
      this.editPlatformId = 0
      this.showForm = true
    }
  }
  
}
</script>

<style scoped>
</style>
