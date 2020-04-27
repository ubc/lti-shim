<template>
  <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
    <a class="dropdown-item" :href='accountPath'>{{ name }}</a>
    <div class="dropdown-divider"></div>
    <a class="dropdown-item" :href='logoutPath' @click.prevent='logout'>
      {{ logoutText }} abc
    </a>

    <form id="logout-form" :action="logoutPath" method="POST">
      <slot></slot>
    </form>
  </div>
</template>

<script>
export default {
	name: 'SessionDropdown',
  props: ['accountPath', 'logoutPath', 'logoutText', 'name'],
  methods: {
    logout() {
      // delete our API tokens then let the regular log out proceed
      this.$store.dispatch('auth/signOut').then(() => {
        document.getElementById("logout-form").submit()
      })
    }
  }
}
</script>

<style scoped>
#logout-form {
  display: none;
}
</style>
