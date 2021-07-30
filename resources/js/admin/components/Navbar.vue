<template>
  <nav class="bg-ubcblue text-white p-4 flex justify-between">
      <router-link class='font-bold flex-initial md:text-xl sm:text-sm' to='/'>
        {{ appName }}
      </router-link>

      <router-link class='nav-link' :to="{name: 'admin'}" v-if='isLoggedIn'>
        Admin
      </router-link>

      <div>
        <router-link class='nav-link' :to="{name: 'login'}"
          v-if='!isLoggedIn'>
          Login
        </router-link>
        <a href='#' @click='logout' class='nav-link' v-if='isLoggedIn'>
          Logout
        </a>
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
