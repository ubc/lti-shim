<template>
  <div class='row justify-content-center'>
    <div class='col col-lg-5 col-md-8'>
      <div class='card'>
        <div class='card-header'>Login</div>

        <div class='card-body'>
          <form @submit.prevent='login'>
            <div class='form-group'>

              <label for='email'>Email</label>
              <input type='email' class='form-control' id='email' ref='email'
                     aria-describedby='emailHelp' required v-model='email' />
            </div>

            <div class='form-group'>
              <label for='password'>Password</label>
              <input type='password' class='form-control' id='password' required
                     v-model='password' />
            </div>

            <button type='submit' class='btn btn-primary' v-if='!isLoading'>
              Login</button>
            <div class="spinner-border" role="status" v-if='isLoading'>
              <span class="sr-only">Loading...</span>
            </div>

          </form>
        </div>

      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "LoginView",
  data() { return {
    email: '',
    password: '',
    isLoading: false
  }},
  methods: {
    login() {
      let credential = {
        email: this.email,
        password: this.password
      }
      this.isLoading = true
      this.$store.dispatch('auth/login', credential).then(() => {
        // when we initiate an api call right after login, the calls all fail
        // with unauthenticated error. This is stupid but I don't want to have
        // to dig through Sanctum's innards, so adding a short delay here
        // as a workaround
        setTimeout(
          ()=>{ this.isLoading = false; this.$router.push('admin') }, 1500)
      })
    }
  },
  mounted() {
    this.$refs.email.focus();
  }
}
</script>

<style scoped>
</style>
