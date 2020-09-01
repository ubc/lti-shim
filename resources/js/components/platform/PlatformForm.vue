<template>
	<div>
		<h3 v-if='isEdit'>Edit Platform</h3>
		<h3 v-else>Add Platform</h3>
    <form @submit.prevent='save'>
      <div class='form-group'>
        <label for='name'>Name</label>
        <input id='name' type='text' class='form-control' required
          v-model='platform.name' />
      </div>

      <div class='form-group'>
        <label for='iss'>ISS</label>
        <input id='iss' type='iss' class='form-control' required
               aria-describedby='issHelp'
               v-model='platform.iss'
               placeholder='https://ubc.test.instructure.com/'
          />
        <small id="issHelp" class="form-text text-muted">
          OAuth issuer, this is usually just the url of the platform.
        </small>
      </div>

      <div class='form-group'>
        <label for='auth_req_url'>Authentication Request URL</label>
        <input id='auth_req_url' type='auth_req_url' class='form-control'
               required
               aria-describedby='authReqUrlHelp'
               v-model='platform.auth_req_url'
               placeholder='https://ubc.test.instructure.com/api/lti/authorize'
               />
        <small id="authReqUrlHelp" class="form-text text-muted">
          Platform endpoint for step 2 of the LTI Launch.
        </small>
      </div>

      <JwkForm @deleteJwk='deleteJwk'
        :url='platform.jwks_url' @url='platform.jwks_url = $event'
        :keys='platform.keys'  @keys='platform.keys = $event' />

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
	name: 'PlatformForm',
  components: {
    CancelIcon,
    JwkForm,
    SaveIcon,
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
