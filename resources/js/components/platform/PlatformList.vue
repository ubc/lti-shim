<template>
	<div>
    <table class="table table-hover">
      <thead class="thead-light">
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
          <td class='d-flex justify-content-between'>
            <button type='button' class='btn btn-outline-secondary'
              @click="$emit('edit', platform.id)">
              <EditIcon /> Edit
            </button>
            <AreYouSureButton 
              v-if='Object.keys(platforms).length > 1 && platform.id != 1'
              :css="'btn btn-outline-danger'"
              :warning="'Delete platform ' + platform.name + '?'"
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
