<template>
  <div>
    <Banner />
    <div class='mt-4 p-4 mx-auto w-80'>
      <h1 class='text-center text-xl'>Login</h1>

      <form @submit.prevent='login' class='plainForm'>
        <label for='email'>Email</label>
        <input type='email' id='email' ref='email'
               aria-describedby='emailHelp' required v-model='email' />

        <label for='password'>Password</label>
        <input type='password' id='password' required
               v-model='password' />

        <button type='submit' :disabled='isLoading'>
          <Spinner v-if='isLoading' />
          Login
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import Banner from '../components/Banner'
import Spinner from '../components/util/Spinner'

export default {
  name: "LoginView",
  components: {
    Spinner,
    Banner,
  },
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
      this.$store.dispatch('auth/login', credential)
        .then(() => {
          // when we initiate an api call right after login, the calls all fail
          // with unauthenticated error. This is stupid but I don't want to have
          // to dig through Sanctum's innards, so adding a short delay here
          // as a workaround
          setTimeout(
            ()=>{ this.isLoading = false; this.$router.push('admin') }, 1000)
        })
        .catch(() => {
          this.isLoading = false;
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
