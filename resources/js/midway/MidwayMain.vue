<template>
  <div>

    <div class='d-lg-flex justify-content-lg-between align-items-lg-center'>
      <form :action='action' :method='method' ref='form' class='order-lg-1'>
        <slot name='session'></slot>
        <button type='submit' class='btn btn-primary' ref='continueButton'>
          <ContinueIcon />
          Continue to {{ tool }}
        </button>
      </form>
      <h1 class='order-lg-0 mt-3 mt-lg-0'>
        {{ tool }} Student Identities
      </h1>
    </div>

    <p>
    Students in the tool you are accessing receive anonymous identities to
    protect their privacy. Find and click any identity used in {{ tool }} or in
    {{ platform }} to reveal who that student is on the other side.
    </p>

    <div>
      <slot name='users'></slot>
    </div>

  </div>
</template>

<script>
//import ContinueIcon from 'icons/ArrowTopRightThinCircleOutline'
import ContinueIcon from 'icons/ArrowTopRightThick'

export default {
  name: "MidwayMain",
  components: {
    ContinueIcon
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
    platform: {
      type: String,
      required: true
    },
    tool: {
      type: String,
      required: true
    }
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
