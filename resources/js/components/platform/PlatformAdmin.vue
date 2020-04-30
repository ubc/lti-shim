<template>
	<div class='card'>

		<h2 class='card-header'>Platforms</h2>
    
    <div class='card-body'>
      <PlatformForm v-if='showForm' :platformId='editPlatformId' 
        @done='showForm = false'/>
      <div v-else>
        <button type='button' class='btn btn-outline-primary mb-3' @click='add'>
          <AddIcon /> Add Platform
        </button>
        <PlatformList @edit='edit' />
      </div>
    </div>

	</div>
</template>

<script>
import AddIcon from 'icons/Plus'

import PlatformForm from './PlatformForm'
import PlatformList from './PlatformList'

export default {
	name: "PlatformAdmin",
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
    add() {
      this.editPlatformId = 0
      this.showForm = true
    },
    edit(platformId) {
      this.editPlatformId = platformId
      this.showForm = true
    },
  }
}
</script>

<style scoped>
</style>
