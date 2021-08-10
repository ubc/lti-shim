<!-- This used to be the original navbar but is not only used on the login and
  splash pages. -->

<template>
  <nav class="bg-ubcblue p-4 flex justify-between">
      <router-link class='text-white font-bold flex-initial md:text-xl
        sm:text-sm' to='/'>
        {{ appName }}
      </router-link>

      <router-link class='text-white' :to="{name: 'admin'}" v-if='isLoggedIn'>
        Admin
      </router-link>

      <div>
        <router-link class='text-white' :to="{name: 'login'}"
          v-if='!isLoggedIn'>
          Login
        </router-link>
        <a href='#' @click='logout' class='text-white' v-if='isLoggedIn'>
          Logout
        </a>
      </div>
  </nav>
</template>

<script>
export default {
  name: "Banner",
  computed: {
    appName() {
      return this.$store.state.appName
    },
    isLoggedIn() {
      return this.$store.state.auth.isLoggedIn
    }
  },
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
