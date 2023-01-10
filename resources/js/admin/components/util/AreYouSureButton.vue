<template>
  <div>
    <button type='button' :class='css' @click='showConfirm=true'
            v-if='!showConfirm'>
      <slot></slot>
    </button>
    <div class='text-red-600 flex flex-col' v-if='showConfirm'>
      <div>{{ warning }}</div>
      <div class='flex-none m-auto'>
        <button type='button' class='btnDanger btnSm'
          @click="$emit('yes'); showConfirm=false">
          Yes
        </button>
        <button type='button' class='btnSecondary btnSm ml-2'
          @click='showConfirm=false'>
          No
        </button>
      </div>
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
      default: 'btnDanger'
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
