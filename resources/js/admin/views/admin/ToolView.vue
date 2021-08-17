<template>
  <div>
    <h2>Tools</h2>

    <div>
      <ToolForm v-if='showForm' @done='$router.push({name: "adminTool"})'
        :toolId='toolId' />
      <div v-else>
        <p>
        LTI tools are usually more specialized learning apps (e.g.: Blogs,
        Webwork, iPeer) that receive LTI launches and information.
        </p>
        <router-link class='btnPrimary mb-2' tag='button'
                     :to="{name: 'addTool'}">
          <AddIcon /> Add Tool
        </router-link>
        <ToolList />
      </div>
    </div>

  </div>
</template>

<script>
import AddIcon from 'icons/Plus'

import ToolForm from '@admin/components/tool/ToolForm'
import ToolList from '@admin/components/tool/ToolList'

export default {
  name: "ToolView",
  components: {
    AddIcon,
    ToolForm,
    ToolList
  },
  data() { return {
    showForm: false,
    toolId: 0
  }},
  methods: {
  },
  mounted() {
    if (this.$route.name == 'editTool') {
      this.toolId = this.$route.params.toolId
      this.showForm = true
    }
    else if (this.$route.name == 'addTool') {
      this.toolId = 0
      this.showForm = true
    }
  }
}
</script>

<style scoped>
</style>
