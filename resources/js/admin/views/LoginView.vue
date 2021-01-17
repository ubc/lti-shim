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

            <button type='submit' class='btn btn-primary'>Login</button>
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
    password: ''
  }},
  methods: {
    login() {
      let credential = {
        email: this.email,
        password: this.password
      }
      this.$store.dispatch('auth/login', credential).then(() => {
        this.$router.push('admin')
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
