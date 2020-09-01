<template>
  <div>
    <h3 v-if='isEdit'>Edit Tool</h3>
    <h3 v-else>Add Tool</h3>

    <form @submit.prevent='save'>
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" class="form-control" id="name"  required
          v-model='tool.name'>
      </div>
      <div class="form-group">
        <label for="clientId">Shim Client ID</label>
        <input type="text" class="form-control" id="clientId" required
               v-model='tool.client_id' aria-describedby='clientIdHelp'>
        <small id="clientIdHelp" class="form-text text-muted">
          Give this tool a client ID. When the tool talks to the shim, they
          must provide this ID to identity themselves.
        </small>
      </div>
      <div class="form-group">
        <label for="oidcLoginUrl">OIDC Login URL</label>
        <input type="text" class="form-control" id="oidcLoginUrl" required
          v-model='tool.oidc_login_url' aria-describedby='oidcLoginUrlHelp'>
        <small id="oidcLoginUrlHelp" class="form-text text-muted">
          Tool endpoint to initiate the first step in an LTI launch.
        </small>
      </div>
      <div class="form-group">
        <label for="authRespUrl">Authentication Response URL</label>
        <input type="text" class="form-control" id="authRespUrl" required
          v-model='tool.auth_resp_url' aria-describedby='authRespUrlHelp'>
        <small id="authRespUrlHelp" class="form-text text-muted">
          Tool endpoint for the last step in an LTI launch
        </small>
      </div>
      <div class="form-group">
        <label for="targetLinkUrl">Target Link URL</label>
        <input type="text" class="form-control" id="targetLinkUrl" required
          v-model='tool.target_link_uri' aria-describedby='targetLinkUrlHelp'>
        <small id="targetLinkUrlHelp" class="form-text text-muted">
          Location in the tool where the user should end up after an LTI launch.
        </small>
      </div>

      <JwkForm @deleteJwk='deleteJwk'
        :url='tool.jwks_url' @url='tool.jwks_url = $event'
        :keys='tool.keys'  @keys='tool.keys = $event' />

      <button type='submit' class='btn btn-outline-primary'
        :disabled='isWaiting'>
        <span class="spinner-border spinner-border-sm" role="status"
          aria-hidden="true" v-if='isWaiting'></span>
        <SaveIcon v-else />
        Save
      </button>
      <button type='button' class='btn btn-outline-secondary'
        @click="$emit('done')" :disabled='isWaiting'>
        <CancelIcon />
        Cancel
      </button>

    </form>
  </div>
</template>

<script>
import CancelIcon from 'icons/Cancel'
import SaveIcon from 'icons/ContentSave'

import JwkForm from '../jwk/JwkForm'

export default {
  name: "ToolForm",
  components: {
    CancelIcon,
    JwkForm,
    SaveIcon,
  },
  computed: {
    isEdit() { return this.toolId > 0 },
  },
  props: {
    toolId: {
      type: Number,
      default: 0
    },
  },
  data() { return {
    tool: {
      name: '',
      client_id: '',
      oidc_login_url: '',
      auth_resp_url: '',
      target_link_uri: '',
      jwks_url: '',
      keys: [],
    },
    isWaiting: false
  }},
  methods: {
    save() {
      let action = 'tool/add'
      if (this.isEdit) action = 'tool/edit'

      this.isWaiting = true
      this.$store.dispatch(action, this.tool)
        .then(() => {
          this.$emit('done')
          this.isWaiting = false
        })
        .catch(() => {
          this.isWaiting = false
        })
    },
    deleteJwk(keyId) {
      this.$store.dispatch('tool/deleteKey',
        {'toolId': this.toolId, 'keyId': keyId})
        .then(response => {
          for (const [index, key] of this.tool.keys.entries()) {
            if (key.id == keyId) {
              this.tool.keys.splice(index, 1)
              break
            }
          }
        })
    }
  },
  mounted() {
    if (this.isEdit) {
      this.$store.dispatch('tool/getCopy', this.toolId)
        .then((toolCopy) => {
          this.tool = toolCopy
        })
    }
  }
}
</script>

<style scoped>
</style>
