<template>
	<div class='card'>
		<h2 class='card-header'>Tools</h2>
    <div class='card-body'>
      <ToolForm v-if='showForm' @done='showForm=false' :toolId='toolId' />
      <div v-else>
        <p class='text-muted'>
        LTI tools are usually more specialized learning apps that receive LTI
        launches and information.
        </p>
        <button type='button' class='btn btn-outline-primary mb-3' @click='add'>
          <AddIcon /> Add Tool
        </button>
        <ToolList @edit='edit' />
      </div>
    </div>
	</div>
</template>

<script>
import AddIcon from 'icons/Plus'

import ToolForm from './ToolForm'
import ToolList from './ToolList'

export default {
	name: "ToolAdmin",
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
