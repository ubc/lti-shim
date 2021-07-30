<template>
	<div class='plainForm mt-4'>
    <h4>RSA Public Keys</h4>
    <p class='textMuted mb-0'>
    Keys must follow the JSON Web Key (JWK) standard.
    </p>

    <label for='jwks_url'>JWKS URL</label>
    <input id='jwks_url' type='text' aria-describedby='jwksUrlHelp'
           :value='url' @input="$emit('url', $event.target.value)" />
    <small id="jwksUrlHelp" class='mb-4'>
      Endpoint where we can grab a list of public keys.
    </small>

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
