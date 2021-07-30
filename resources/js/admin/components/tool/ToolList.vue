<template>
  <div>
    <table class="plainTable">
      <thead>
        <tr>
          <th scope="col">ID</th>
          <th scope="col">Name</th>
          <th scope="col">Shim Client ID</th>
          <th scope="col"></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for='tool in tools'>
          <th scope="row">{{ tool.id }}</th>
          <td>{{ tool.name }}</td>
          <td>{{ tool.client_id }}</td>
          <td class='flex justify-between gap-2'>
            <button type='button' class='btnSecondary'
              @click="$emit('edit', tool.id)">
              <EditIcon /> Edit
            </button>
            <AreYouSureButton
              :css="'btnDanger'"
              :warning="'Delete tool ' + tool.name + '?'"
              @yes='deleteTool(tool.id)'>
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
  name: "ToolList",
  components: {
    AreYouSureButton,
    DeleteIcon,
    EditIcon,
  },
  computed: {
    tools() { return this.$store.state.tool.items }
  },
  methods: {
    deleteTool(toolId) {
      this.$store.dispatch('tool/delete', toolId)
    }
  },
  mounted() {
    this.$store.dispatch('tool/getAll')
  }
}
</script>

<style scoped>
</style>
