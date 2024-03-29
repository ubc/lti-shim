<template>
  <div>
    <h3 v-if='isEdit'>Edit Tool</h3>
    <h3 v-else>Add Tool</h3>

    <form @submit.prevent='save' class='plainForm'>
      <label for="name">Name</label>
      <input type="text" id="name" required v-model='tool.name'>

      <label for="clientId">Shim Client ID</label>
      <input type="text" id="clientId" required
             v-model='tool.client_id' aria-describedby='clientIdHelp'>
      <small id="clientIdHelp">
        Give this tool a client ID. When the tool talks to the shim, they
        must provide this ID to identify themselves.
      </small>

      <label for="oidcLoginUrl">OIDC Login URL</label>
      <input type="text" id="oidcLoginUrl" required
        v-model='tool.oidc_login_url' aria-describedby='oidcLoginUrlHelp'>
      <small id="oidcLoginUrlHelp">
      </small>

      <label for="authRespUrl">Redirect URL</label>
      <input type="text" id="authRespUrl" required
        v-model='tool.auth_resp_url' aria-describedby='authRespUrlHelp'>
      <small id="authRespUrlHelp">
      </small>

      <label for="targetLinkUrl">Target Link URL</label>
      <input type="text" id="targetLinkUrl" required
             aria-describedby='targetLinkUrlHelp'
             v-model='tool.target_link_uri'>
      <small id="targetLinkUrlHelp">
        Where the user should end up after an LTI launch. If not given, it's
        probably ok to just put the tool's URL.
      </small>

      <label for="enableMidwayLookup">
        <input type="checkbox" id="enableMidwayLookup" class='mr-2'
               aria-describedby='enableMidwayLookupHelp'
               v-model='tool.enable_midway_lookup'>
        Enable Midway Lookup
      </label>
      <small id="enableMidwayLookupHelp">
        If enabled, instructors will be presented with a student lookup page
        before continuing to the tool. If disabled, the lookup page will still
        be accessible via a specially modified launch.
      </small>

      <JwkForm @deleteJwk='deleteJwk'
        :url='tool.jwks_url' @url='tool.jwks_url = $event'
        :keys='tool.keys'  @keys='tool.keys = $event' />

      <div>
        <button type='submit' class='mr-2'
          :disabled='isWaiting'>
          <Spinner v-if='isWaiting' />
          <SaveIcon v-else />
          Save
        </button>
        <button type='button' class='btnSecondary'
          @click="$emit('done')" :disabled='isWaiting'>
          <CancelIcon />
          Cancel
        </button>
      </div>

    </form>
  </div>
</template>

<script>
import CancelIcon from 'icons/Cancel'
import SaveIcon from 'icons/ContentSave'

import JwkForm from '../jwk/JwkForm'
import Spinner from '../util/Spinner'

export default {
  name: "ToolForm",
  components: {
    CancelIcon,
    JwkForm,
    SaveIcon,
    Spinner
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
      enable_midway_lookup: false
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
