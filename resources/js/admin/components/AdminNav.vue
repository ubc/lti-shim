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
    <router-link class='navItem mx-2 md:mx-0 md:mt-2' :to="{name: 'account'}" v-if='isLoggedIn'>
      <AccountIcon class='text-xl md:text-lg' title='Edit Account Settings' />
      <span class='hidden md:inline'>Account</span>
    </router-link>
    <a class='navItem' href='#'  @click='logout'>
      <LogoutIcon class='text-xl md:text-lg'  title='Logout' />
      <span class='hidden md:inline'>Logout</span>
    </a>
  </div>
</template>

<script>
import AccountIcon from 'icons/AccountCircleOutline'
import LogoutIcon from 'icons/Logout'

export default {
  name: "AdminNav",
  components: {
    AccountIcon,
    LogoutIcon,
  },
  computed: {
    appName() {
      return this.$store.state.appName
    },
    isLoggedIn() {
      return this.$store.state.auth.isLoggedIn
    },
  },
  data() { return {
  }},
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
