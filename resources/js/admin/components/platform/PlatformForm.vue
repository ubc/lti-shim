<template>
  <div>
    <h3 v-if='isEdit'>Edit Platform</h3>
    <h3 v-else>Add Platform</h3>
    <form @submit.prevent='save' class='plainForm'>
      <label for='name'>Name</label>
      <input id='name' type='text' required v-model='platform.name' />

      <label for='iss'>ISS</label>
      <input id='iss' type='text' required
             aria-describedby='issHelp'
             v-model='platform.iss'
             placeholder='https://ubc.test.instructure.com/'
             />
      <small id="issHelp">
        OAuth issuer, this is usually just the url of the platform.
      </small>

      <label for='auth_req_url'>Authentication Request URL</label>
      <input id='auth_req_url' type='text' required
             aria-describedby='authReqUrlHelp'
             v-model='platform.auth_req_url'
             placeholder='https://ubc.test.instructure.com/api/lti/authorize'
             />
      <small id="authReqUrlHelp">
        Platform endpoint for step 2 of the LTI Launch.
      </small>

      <label for='access_token_url'>Access Token URL</label>
      <input id='access_token_url' type='text'
             aria-describedby='accessTokenUrlHelp'
             v-model='platform.access_token_url'
             placeholder='https://ubc.test.instructure.com/login/oauth2/token'
             />
      <small id="accessTokenUrlHelp">
        Platform endpoint for OAuth2 access token requests, only required to
        enable LTI services (get class roster, grades sync, etc).
      </small>

      <JwkForm @deleteJwk='deleteJwk'
        :url='platform.jwks_url' @url='platform.jwks_url = $event'
        :keys='platform.keys'  @keys='platform.keys = $event' />

      <div>
        <button type='submit' class='mr-2'
          :disabled='isWaiting'>
          <Spinner v-if='isWaiting' />
          <SaveIcon v-else />
          Save
        </button>
        <button type='button' class='btnSecondary'
          @click="$router.back()" :disabled='isWaiting'>
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
  name: 'PlatformForm',
  components: {
    CancelIcon,
    JwkForm,
    SaveIcon,
    Spinner
  },
  props: {
    platformId: {
      type: Number,
      default: 0
    },
    isEditSelf: {
      type: Boolean,
      default: false
    }
  },
  computed: {
    isEdit() { return this.platformId > 0 },
  },
  data() { return {
    platform: {
      name: '',
      iss: '',
      auth_req_url: '',
      access_token_url: '',
      jwks_url: '',
      keys: []
    },
    isWaiting: false,
  }},
  methods: {
    save() {
      let action = 'platform/add'
      if (this.isEdit) action = 'platform/edit'

      this.isWaiting = true
      this.$store.dispatch(action, this.platform)
        .then(() => {
          this.isWaiting = false
          this.$emit('done')
        })
        .catch(() => {
          this.isWaiting = false
        })
    },
    deleteJwk(keyId) {
      this.$store.dispatch('platform/deleteKey',
        {'platformId': this.platformId, 'keyId': keyId})
        .then(response => {
          for (const [index, key] of this.platform.keys.entries()) {
            if (key.id == keyId) {
              this.platform.keys.splice(index, 1)
              break
            }
          }
        })
    }
  },
  mounted() {
    if (this.isEdit) {
      this.$store.dispatch('platform/getCopy', this.platformId)
        .then((platformCopy) => {
          this.platform = platformCopy
        })
    }
  }
}
</script>

<style scoped>
</style>
