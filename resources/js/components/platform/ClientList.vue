<template>
	<fieldset class='form-group'>
    <legend>Client ID</legend>
    <p class='text-muted'>
    Adding the shim as a tool on the platform should have generated a client ID,
    enter it here.
    </p>
    <div class='form-inline mb-2'>
      <label for='newClientId' class='mr-2'>Client ID</label>
      <input id='newClientId' type='text' class='form-control mr-2'
        v-model='newClientId' />
      <div class='input-group-append'>
        <button class='btn btn-outline-primary' type='button' id='clientAdd'
          @click='add'>
          <AddIcon /> Add Client
        </button>
      </div>
    </div>
    <p v-if='value.length == 0' class='text-danger'>
    No client IDs, please add at least one!
    </p>
    <ul v-else class='list-group'>
      <li v-for='client in value' :key='client.client_id'
        class='list-group-item d-flex'>
        <div class='flex-grow-1 align-self-center'>
          {{ client.client_id }}
        </div>
        <div>
          <AreYouSureButton :css="'btn btn-sm btn-outline-danger'"
             :warning="'Delete client id ' + client.cliend_id + '?'"
             @yes='deleteClient(client.client_id)'>
            <DeleteIcon /> Delete
          </AreYouSureButton>
        </div>
      </li>
    </ul>
	</fieldset>
</template>

<script>
import AddIcon from 'icons/Plus'
import DeleteIcon from 'icons/Delete'

import AreYouSureButton from '../util/AreYouSureButton'

export default {
	name: "ClientList",
  components: {
    AreYouSureButton,
    AddIcon,
    DeleteIcon,
  },
  props: {
    value: { type: Array, default() { return [] }}
  },
  data() { return {
    newClientId: ''
  }},
  methods: {
    add() {
      if (!this.newClientId) return
      for (const client of this.value) {
        if (client.client_id == this.newClientId) {
          this.$notify({
            'title': 'Cannot add duplicate client ID: ' + this.newClientId,
            'type': 'warn'
          })
          return
        }
      }
      this.value.push({'client_id': this.newClientId})
      this.$emit('input', this.value)
    },
    deleteClient(clientId) {
      for (const [index, client] of this.value.entries()) {
        if (client.client_id == clientId) {
          if ('id' in client) // if there's an id, api call needed to delete
            this.$emit('delete', client.id)
          else
            this.value.splice(index, 1)
          break
        }
      }
      this.$emit('input', this.value)
    }
  },
}
</script>

<style scoped>
</style>
