<template>
  <div>

    <div class='d-lg-flex justify-content-lg-between align-items-lg-center'>
      <form :action='action' :method='method' ref='form' class='order-lg-1'>
        <slot name='session'></slot>
        <button type='submit' class='btn btn-primary' ref='continueButton'>
          <ContinueIcon />
          Continue to {{ toolName }}
        </button>
      </form>
      <h1 class='order-lg-0 mt-3 mt-lg-0'>
        {{ toolName }} Student Identities
      </h1>
    </div>

    <p>
    Students in the tool you are accessing receive anonymous identities to
    protect their privacy. Find and click any identity used in {{ toolName }}
    or in {{ platformName }} to reveal who that student is on the other side.
    </p>

    <div>
      <UserList />
    </div>

  </div>
</template>

<script>
//import ContinueIcon from 'icons/ArrowTopRightThinCircleOutline'
import ContinueIcon from 'icons/ArrowTopRightThick'
import UserList from '../UserList'

export default {
  name: "MidwayMain",
  components: {
    ContinueIcon,
    UserList
  },
  props: {
    action: {
      type: String,
      required: true
    },
    method: {
      type: String,
      required: true,
      validator(value) {
        return ['post', 'get'].indexOf(value) !== -1
      }
    },
    courseContextId: {
      type: String,
      required: true
    },
    platformName: {
      type: String,
      required: true
    },
    token: {
      type: String,
      required: true
    },
    toolId: {
      type: String,
      required: true
    },
    toolName: {
      type: String,
      required: true
    },
  },
  data() { return {
  }},
  methods: {
  },
  mounted() {
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
    this.$store.commit('lookup/setCourseContextId', this.courseContextId)
    this.$store.commit('lookup/setPlatformName', this.platformName)
    this.$store.commit('lookup/setToolName', this.toolName)
    this.$store.commit('lookup/setToolId', this.toolId)
    this.$store.dispatch('lookup/getUsers')
    this.$refs.continueButton.focus();
  }
}
</script>

<style scoped>
</style>
