<template>
  <form :action='action' :method='method' ref='form'>
    <div class='alert alert-info'>
      Pause here if you wish to look up the real identity of a user. Otherwise, the page will automatically continue on to the application.
    </div>

    <div v-show='!isPaused'>
      <p class='h1 text-center'>
        {{ count }}
        <button type='button' @click='pause' class='btn btn-primary btn-lg ml-2'>
          <PauseIcon />
          Pause
        </button>
        <button type='submit' class='btn btn-secondary'>
          <PlayIcon />
          Continue
        </button>
      </p>
    </div>

    <div>
      <slot name='inputs'></slot>
    </div>

    <div v-if='isPaused'>
      <p class='text-center text-muted'>
        <button type='submit' class='btn btn-primary btn-lg ml-2'>
          <PlayIcon />
          Continue
        </button>
      </p>
      <slot name='users'></slot>
    </div>

  </form>
</template>

<script>
import InfoIcon from 'icons/InformationOutline'
import PauseIcon from 'icons/Pause'
import PlayIcon from 'icons/Play'

export default {
  name: "TimedSubmitForm",
  components: {
    InfoIcon,
    PauseIcon,
    PlayIcon
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
    wait: {
      type: Number,
      default: 10
    }
  },
  data() { return {
    count: this.wait,
    interval: "",
    isPaused: false
  }},
  methods: {
    pause() {
      clearInterval(this.interval)
      this.isPaused = true
    },
  },
  mounted() {
    this.interval = setInterval(() => {
      this.count -= 1
      if (this.count <= 0) {
        clearInterval(this.interval)
        this.$refs.form.submit()
      }
    }, 1000)
  }
}
</script>

<style scoped>
</style>
