<template>
  <form :action='action' method='post' ref='form' v-if='!isMidwayOnly'>
    <label for='state' class='hidden'>State</label>
    <input v-if='continuationState' type='hidden' id='state' name='state'
           :value='continuationState' />

    <label for='id_token' class='hidden'>ID Token</label>
    <input type='hidden' id='id_token' name='id_token'
           :value='continuationIdToken' />

    <button type='submit' ref='continueButton'
      class='text-base md:text-lg btnPrimary p-2'>
      <ContinueIcon />
      Continue to {{ toolName }}
    </button>
  </form>
</template>

<script>
import ContinueIcon from 'icons/ArrowTopRightThick'

export default {
  name: "ContinuationForm",
  components: {
    ContinueIcon,
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
    isMidwayOnly: {
      type: Boolean,
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
    this.$refs.continueButton.focus();
  }
}
</script>

<style scoped>
</style>
