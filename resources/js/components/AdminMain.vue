<template>
	<div>
    <h1>Admin</h1>
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
	name: "AdminMain",
  components: {
  },
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
