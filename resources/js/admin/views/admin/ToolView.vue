<template>
  <div>
    <h2>Tools</h2>

    <div>
      <ToolForm v-if='showForm' @done='showForm=false' :toolId='toolId' />
      <div v-else>
        <p>
        LTI tools are usually more specialized learning apps (e.g.: Blogs,
        Webwork, iPeer) that receive LTI launches and information.
        </p>
        <button type='button' class='btnPrimary mb-2' @click='add'>
          <AddIcon /> Add Tool
        </button>
        <ToolList @edit='edit' />
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
    add() {
      this.toolId = 0
      this.showForm = true
    },
    edit(toolId) {
      this.toolId = toolId
      this.showForm = true
    }
  }
}
</script>

<style scoped>
</style>
