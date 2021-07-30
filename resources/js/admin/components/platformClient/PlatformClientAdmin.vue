<template>
  <div>
    <h2>Platform Clients</h2>

    <form @submit.prevent='save' v-show='!showList' class='plainForm mb-8'>
      <label for='platformSelect'>Platform</label>
      <select id='platformSelect'
        v-model='clientForm.platform_id'>
        <option v-for='platform in platforms' :value='platform.id'>
          {{ platform.name }}
        </option>
      </select>

      <label for='toolSelect'>Target Tool</label>
      <select id='toolSelect'
        v-model='clientForm.tool_id'>
        <option v-for='tool in tools' :value='tool.id'>
          {{ tool.name }}
        </option>
      </select>

      <label for='clientId'>Client ID</label>
      <input type='text' id='clientId'
        v-model='clientForm.client_id'></input>

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

    <p v-show='showList'>
    When a tool is added to a platform, the platform will assign a client ID
    to the tool. Enter that client ID here. See the "Shim Configuration
    Section" below for configuration values you should enter in the platform.
    </p>

    <button type='button' class='btnPrimary mb-4' @click='add'
            v-show='showList'>
      <AddIcon /> Add Platform Client
    </button>

      <table class="plainTable" v-show='showList'>
        <thead>
          <tr>
            <th scope="col">ID</th>
            <th scope="col">Platform</th>
            <th scope="col">Target Tool</th>
            <th scope="col">Client ID</th>
            <th scope="col"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for='platformClient in platformClients'>
            <th scope="row">{{ platformClient.id }}</th>
            <td>{{ platforms[platformClient.platform_id].name }}</td>
            <td>{{ tools[platformClient.tool_id].name }}</td>
            <td>{{ platformClient.client_id }}</td>
            <td class='flex justify-between gap-2'>
              <button type='button' class='btnSecondary'
                @click="edit(platformClient.id)">
                <EditIcon /> Edit
              </button>
              <AreYouSureButton
                :css="'btnDanger'"
                :warning="'Delete platformClient ' + platformClient.name + '?'"
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
    platforms() { return this.$store.state.platform.items },
    tools() { return this.$store.state.tool.items },
    platformClients() { return this.$store.state.platformClient.items },
  },
  data() { return {
    isEdit: false,
    isWaiting: false,
    showList: true,
    clientForm: {
      platform_id: '',
      tool_id: '',
      client_id: ''
    }
  }},
  methods: {
    add() {
      this.clientForm = {
        platform_id: '',
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
      this.$store.dispatch('platformClient/delete', platformClientId)
    },
    edit(platformClientId) {
      this.$store.dispatch('platformClient/getCopy', platformClientId)
        .then((copy) => { this.clientForm = copy; this.showList = false })
      this.isEdit = true
    },
    save() {
      let action = 'platformClient/add'
      if (this.isEdit) action = 'platformClient/edit'

      this.isWaiting = true
      this.$store.dispatch(action, this.clientForm)
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
    this.$store.dispatch('platformClient/getAll')
  }
}
</script>

<style scoped>
</style>
