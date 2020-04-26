<template>
  <div>
    <button type='button' :class='css' @click='showConfirm=true'
            v-if='!showConfirm'>
      <slot></slot>
    </button>
    <div class='text-danger' v-if='showConfirm'>
      {{ warning }}
      <button type='button' class='btn btn-outline-danger btn-sm ml-1'
        @click="$emit('yes'); showConfirm=false">
        Yes
      </button>
      <button type='button' class='btn btn-outline-secondary btn-sm ml-1'
        @click='showConfirm=false'>
        No
      </button>
    </div>
  </div>
</template>

<script>
export default {
	name: "AreYouSureButton",
  props: {
    warning: {
      type: String,
      default: 'Are you sure?'
    },
    css: {
      type: String,
      default: 'btn btn-outline-danger'
    }
  },
  data() { return {
    showConfirm: false
  }},
  methods: {
  }
}
</script>

<style scoped>
</style>
