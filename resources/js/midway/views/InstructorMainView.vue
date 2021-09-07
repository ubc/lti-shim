<template>
  <div class='mx-auto p-2'>

    <div class='flex flex-col'>
      <ContinuationForm :action='action' :tool-name='toolName'
         :continuation-id-token='continuationIdToken'
         :continuation-state='continuationState'
         :is-midway-only='isMidwayOnly' />

      <h1 class='my-2'>
        {{ toolName }} Student Identities
      </h1>
    </div>
    <p class='mb-4'>
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
import ContinuationForm from '@midway/components/ContinuationForm'
import UserList from '@midway/components/UserList'

export default {
  name: "MidwayMain",
  components: {
    ContinuationForm,
    UserList
  },
  props: {
    action: {
      type: String,
      required: true
    },
    continuationIdToken: {
      type: String,
      required: true
    },
    continuationState: {
      type: String
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
    isMidwayOnly: {
      type: Boolean,
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
  },
  created() {
    // Initialization needs to be in created() cause mounted() is too late.
    // Child components will make api calls before our mounted() is called.
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token;
    this.$store.commit('lookup/setCourseContextId', this.courseContextId)
    this.$store.commit('lookup/setPlatformName', this.platformName)
    this.$store.commit('lookup/setToolName', this.toolName)
    this.$store.commit('lookup/setToolId', this.toolId)
  }
}
</script>

<style scoped>
</style>
