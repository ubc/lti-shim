<template>
  <div class='form-group'>
    <label>JWK</label>
    <p class='text-muted'>
    If a JWKS URL is not available, you can manually enter keys here.
    Automatically retrieved keys from the JWKS URL are also listed here.
    </p>

    <div class='form-inline mb-2 d-flex'>
      <label for='newJwk' class='mr-2 sr-only'>JWK</label>
      <textarea id='newJwk' type='text' class='form-control mr-2 flex-grow-1'
                v-model='newJwk' />
        <div class='input-group-append'>
          <button class='btn btn-outline-primary' type='button' id='jwkAdd'
                  @click='add'>
            <AddIcon /> Add JWK
          </button>
        </div>
    </div>

    <p v-if='value.length == 0' class='text-warning'>
    No keys right now.
    </p>

    <ul v-else class='list-group'>
      <li v-for='jwk in value' :key='jwk.kid' class='list-group-item d-flex'>
        <div class='flex-grow-1 align-self-center'>
          <button type='button' class='btn btn-sm btn-outline-secondary'
                  @click="toggleShowJwk(jwk)">
            <UpIcon v-if='jwk.show' />
            <DownIcon v-else />
          </button>
          {{ jwk.kid }}
          <pre v-show='jwk.show'>
          <code>
            {{ jwk.key }}
          </code>
          </pre>
        </div>
        <div>
          <AreYouSureButton :css="'btn btn-sm btn-outline-danger'"
               :warning="'Delete key ' + jwk.kid + '?'"
               @yes='deleteKey(jwk.kid)'>
            <DeleteIcon /> Delete
          </AreYouSureButton>
        </div>
      </li>
    </ul>

  </div>
</template>

<script>
import AddIcon from 'icons/Plus'
import DeleteIcon from 'icons/Delete'
import DownIcon from 'icons/ChevronDown'
import UpIcon from 'icons/ChevronUp'

import AreYouSureButton from '../util/AreYouSureButton'

export default {
  name: "JwkList",
  components: {
    AreYouSureButton,
    AddIcon,
    DeleteIcon,
    DownIcon,
    UpIcon,
  },
  props: {
    value: { type: Array, default() { return [] }}
  },
  data() { return {
    newJwk: ''
  }},
  methods: {
    add() {
      let json = ''
      try {
        json = JSON.parse(this.newJwk)
        // do some basic validation on the jwk
        if (!('kid' in json)) throw new Error('Missing param: kid')
        if (!('kty' in json)) throw new Error('Missing param: kty')
        if (!('n' in json)) throw new Error('Missing param: n')
        if (!('e' in json)) throw new Error('Missing param: e')
      }
      catch(error) {
        this.$notify({
          'title': 'Failed to add JWK',
          'text': error.message,
          'type': 'error'
        })
        return
      }
      for (const jwk of this.value) {
        if (jwk.kid == json.kid) {
          this.$notify({
            'title': 'Cannot add duplicate key ID: ' + json.kid,
            'type': 'warn'
          })
          return
        }
      }
      this.value.push({'kid': json.kid, 'key': this.newJwk})
      this.$emit('input', this.value)
    },
    deleteKey(kid) {
      for (const [index, jwk] of this.value.entries()) {
        if (jwk.kid == kid) {
          if ('id' in jwk)
            this.$emit('delete', jwk.id)
          else
            this.value.splice(index, 1)
          break
        }
      }
      this.$emit('input', this.value)
    },
    toggleShowJwk(jwk) {
      if (jwk.show) {
        this.$set(jwk, 'show', false)
      }
      else {
        this.$set(jwk, 'show', true)
      }
    }
  }
}
</script>

<style scoped>
pre,code {
  word-break: break-all;
  white-space: pre-wrap;
}
</style>
