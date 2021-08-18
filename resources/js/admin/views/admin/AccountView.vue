<template>
	<div>
    <h1>Account</h1>
    <p>Change your own name, email, and password here.</p>
    <Spinner v-if='!isReady' />
    <UserForm :userId='Number(user.id)' :isEditSelf='true' v-if='isReady'>
    </UserForm>
	</div>
</template>

<script>
import Spinner from '@admin/components/util/Spinner'
import UserForm from '@admin/components/user/UserForm'

export default {
	name: "Account",
  components: {
    Spinner,
    UserForm,
  },
  computed: {
    isLoggedIn() {
      return this.$store.state.auth.isLoggedIn
    },
    isReady() {
      return this.isLoggedIn && ('id' in this.user)
    }
  },
  data() { return {
    user: {}
  }},
  mounted() {
    this.$store.dispatch('user/getSelf').then(response => {
      this.user = response.data
    })
  }
}
</script>

<style scoped>
</style>
