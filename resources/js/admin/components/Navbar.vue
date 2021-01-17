<template>
  <nav class="navbar navbar-expand-md navbar-dark bg-dark shadow-sm">
    <div class="container">
      <router-link class='navbar-brand' to='/'>
        {{ appName }}
      </router-link>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <!-- Left Side Of Navbar -->
        <ul class="navbar-nav mr-auto">
          <li class="nav-item">
            <a class='nav-link' href='#'>
              Admin
            </a>
          </li>
        </ul>

        <!-- Right Side Of Navbar -->
        <ul class="navbar-nav ml-auto">
          <!-- Authentication Links -->
          <li class="nav-item">
            <router-link class='nav-link' :to="{name: 'login'}"
              v-if='!isLoggedIn'>
              Login
            </router-link>
            <a href='#' @click='logout' class='nav-link' v-if='isLoggedIn'>
              Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script>
export default {
  name: "Navbar",
  computed: {
    isLoggedIn() {
      return this.$store.state.auth.isLoggedIn
    }
  },
  props: ['appName'],
  methods: {
    logout() {
      this.$store.dispatch('auth/logout').then(() => {
        this.$router.push('/')
      })
    }
  }
}
</script>

<style scoped>
</style>
