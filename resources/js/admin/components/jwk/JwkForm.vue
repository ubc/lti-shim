<template>
	<div>
    <h4>RSA Public Keys</h4>
    <p class='text-muted'>
    Keys must follow the JSON Web Key (JWK) standard.
    </p>
    <div class='form-group'>
      <label for='jwks_url'>JWKS URL</label>
      <input id='jwks_url' type='text' class='form-control'
             :value='url' @input="$emit('url', $event.target.value)"
             aria-describedby='jwksUrlHelp' />
      <small id="jwksUrlHelp" class="form-text text-muted">
        Endpoint where we can grab a list of public keys.
      </small>
    </div>

    <JwkList :value='keys' @input="$emit('keys', $event)"
      @delete="$emit('deleteJwk', $event)" />
	</div>
</template>

<script>
import JwkList from './JwkList'

export default {
	name: "JwkForm",
  props: {
    url: String,
    keys: Array,
  },
  components: {
    JwkList
  },
}
</script>

<style scoped>
</style>
