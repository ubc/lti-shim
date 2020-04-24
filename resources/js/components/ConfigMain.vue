<template>
	<div>
    <div v-if='isSignedIn'>
      Signed In
    </div>
    <div v-else>
      Loading, Please Wait...
    </div>
	</div>
</template>

<script>
export default {
	name: "ConfigMain",
  computed: {
		isSignedIn() {
			return this.$store.getters['auth/isSignedIn']
		},
  },
  mounted() {
    this.$store.dispatch('auth/signIn')
  }
}
</script>

<style scoped>
</style>
