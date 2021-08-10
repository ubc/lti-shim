<template>
  <div class='flex flex-wrap justify-center md:justify-start md:flex-col'>
    <router-link class='navItem font-bold text-2xl w-full text-center
      text-ubcblue'
      to='/'>
      {{ appName }}
    </router-link>
    <router-link class='navItem' :to="{name: 'admin'}" v-if='isLoggedIn'>
      Home
    </router-link>
    <router-link class='navItem' :to="{name: 'adminPlatform'}" v-if='isLoggedIn'>
      Platforms
    </router-link>
    <router-link class='navItem' :to="{name: 'adminTool'}" v-if='isLoggedIn'>
      Tools
    </router-link>
    <router-link class='navItem' :to="{name: 'adminUser'}" v-if='isLoggedIn'>
      Users
    </router-link>
    <a class='navItem' href='#' @click='logout' v-if='isLoggedIn'>
      Logout
    </a>
  </div>
</template>

<script>
export default {
  name: "AdminNav",
  computed: {
    appName() {
      return this.$store.state.appName
    },
    isLoggedIn() {
      return this.$store.state.auth.isLoggedIn
    },
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
.navItem {
  @apply p-1 md:p-2 md:text-lg
}
.router-link-exact-active {
  @apply font-bold
}
</style>
