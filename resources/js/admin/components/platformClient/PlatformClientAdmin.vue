<template>
  <div>
    <h5 class='text-sm mb-1'>Allowed Tools</h5>

    <form @submit.prevent='save' v-show='!showList' class='plainForm mb-8
      border border-ubcblue-100 p-2 md:p-4'>
      <h5 v-if='!isEdit'>Add Allowed Tool</h5>
      <h5 v-if='isEdit'>Edit Allowed Tool</h5>
      <label for='toolSelect'>Tool</label>
      <select id='toolSelect'
        v-model='clientForm.tool_id'>
        <option v-for='tool in tools' :value='tool.id'>
          {{ tool.name }}
        </option>
      </select>

      <label for='clientId'>Client ID</label>
      <input type='text' id='clientId'
        v-model='clientForm.client_id'></input>
      <small id='clientIdHelp'>
      When a tool is added to a platform, the platform will assign a client ID
      to the tool. Enter that client ID here.
      </small>

      <div>
        <button type='submit' class='btnPrimary'
          :disabled='isWaiting'>
          <Spinner v-if='isWaiting' />
          <SaveIcon v-else />
          Save
        </button>
        <button type='button' class='btnSecondary'
          :disabled='isWaiting' @click='cancel()'>
          <CancelIcon /> Cancel
        </button>
      </div>
    </form>


    <button type='button' class='btnSecondary btnSm my-2' @click='add'
            v-show='showList'>
      <AddIcon /> Add Tool
    </button>

      <table class="allowedToolsTable" v-show='showList'>
        <thead>
          <tr>
            <th scope="col">Tool</th>
            <th scope="col">Client ID</th>
            <th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for='platformClient in platformClients'>
            <td>{{ tools[platformClient.tool_id].name }}</td>
            <td>{{ platformClient.client_id }}</td>
            <td class='flex justify-between gap-2'>
              <button type='button' class='btnSm btnSecondary'
                @click="edit(platformClient.id)">
                <EditIcon /> Edit
              </button>
              <AreYouSureButton
                :css="'btnDanger btnSm'"
                :warning="'Delete allowed tool ' +
                  tools[platformClient.tool_id].name + '?'"
                @yes='deletePlatformClient(platformClient.id)'>
                  <DeleteIcon /> Delete
              </AreYouSureButton>
            </td>
          </tr>
        </tbody>
      </table>

  </div>
</template>

<script>
import AddIcon from 'icons/Plus'
import CancelIcon from 'icons/Cancel'
import DeleteIcon from 'icons/Delete'
import EditIcon from 'icons/Pencil'
import SaveIcon from 'icons/ContentSave'

import AreYouSureButton from '../util/AreYouSureButton'
import Spinner from '../util/Spinner'

export default {
  name: "PlatformClientAdmin",
  components: {
    AreYouSureButton,
    AddIcon,
    CancelIcon,
    EditIcon,
    DeleteIcon,
    SaveIcon,
    Spinner
  },
  computed: {
    platformClients() {
      return this.$store.getters['platformClient/getByPlatform'](this.platformId)
    },
    tools() { return this.$store.state.tool.items },
    storeParam() {
      return { 'platformId': this.platformId }
    },
  },
  props: {
    platformId: Number,
  },
  data() { return {
    isEdit: false,
    isWaiting: false,
    showList: true,
    clientForm: {
      platform_id: '',
      tool_id: '',
      client_id: ''
    },
  }},
  methods: {
    add() {
      this.clientForm = {
        platform_id: this.platformId,
        tool_id: '',
        client_id: ''
      }
      this.isEdit = false
      this.showList = false
    },
    cancel() {
      this.showList = true
    },
    deletePlatformClient(platformClientId) {
      this.$store.dispatch('platformClient/delete',
        {'itemId': platformClientId, ...this.storeParam})
    },
    edit(platformClientId) {
      this.$store.dispatch('platformClient/getCopy',
        {'itemId': platformClientId, ...this.storeParam})
        .then((copy) => { this.clientForm = copy; this.showList = false })
      this.isEdit = true
    },
    save() {
      let action = 'platformClient/add'
      if (this.isEdit) action = 'platformClient/edit'

      this.isWaiting = true
      this.$store.dispatch(action, {'item':this.clientForm, ...this.storeParam})
        .then(() => {
          this.isWaiting = false
          this.showList = true
        })
        .catch(() => {
          this.isWaiting = false
        })
    }
  },
  mounted() {
    this.$store.dispatch('tool/getAll')
    this.$store.dispatch('platformClient/getAll', this.storeParam)
  }
}
</script>

<style scoped>
.allowedToolsTable {
  @apply text-sm;
  thead {
    tr { @apply border-gray-200 bg-gray-100; }
    th { @apply py-1; }
  }
  tbody {
    tr { @apply border-gray-200; }
    td { @apply py-2; }
}
}
</style>
