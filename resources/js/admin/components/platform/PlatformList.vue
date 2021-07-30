<template>
	<div>
    <table class="plainTable">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Name</th>
          <th scope="col">ISS</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for='platform in platforms'>
          <th scope="row">{{ platform.id }}</th>
          <td>{{ platform.name }}</td>
          <td>{{ platform.iss }}</td>
          <td class='flex justify-between gap-2'>
            <button type='button' class='btnSecondary'
              @click="$emit('edit', platform.id)">
              <EditIcon /> Edit
            </button>
            <AreYouSureButton 
              :css="'btnDanger'"
              :warning="'Delete platform: ' + platform.name + '?'"
              @yes='deletePlatform(platform.id)'>
                <DeleteIcon /> Delete
            </AreYouSureButton>
          </td>
        </tr>
      </tbody>
    </table>
	</div>
</template>

<script>
import DeleteIcon from 'icons/Delete'
import EditIcon from 'icons/Pencil'

import AreYouSureButton from '../util/AreYouSureButton'

export default {
	name: "PlatformList",
  components: {
    AreYouSureButton,
    DeleteIcon,
    EditIcon,
  },
  computed: {
    platforms() { return this.$store.state.platform.items }
  },
  methods: {
    deletePlatform(itemId) {
      this.$store.dispatch('platform/delete', itemId)
    }
  },
  mounted() {
    this.$store.dispatch('platform/getAll')
  }

}
</script>

<style scoped>
</style>
