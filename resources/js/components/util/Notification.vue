<template>
  <notifications position="top center" :duration='5000'>
    <template slot="body" slot-scope="props">
      <div class='alert mt-1'
           :class="{
             'alert-primary': !props.item.type,
             'alert-success': props.item.type == 'success',
             'alert-danger': props.item.type == 'error',
             'alert-warning': props.item.type == 'warn',
           }">
        <h5 class='mb-0'>
          <InfoIcon v-if="!props.item.type" />
          <SuccessIcon v-if="props.item.type == 'success'" />
          <ErrorIcon v-if="props.item.type == 'error'" />
          <WarnIcon v-if="props.item.type == 'warn'" />
          <strong class='ml-1'>
            {{ props.item.title }}
          </strong>
        </h5>
        <p v-if='props.item.text' class='mb-0'>
          {{ props.item.text }}
        </p>
      </div>
    </template>
  </notifications>
</template>

<script>
import ErrorIcon from 'icons/Alert'
import InfoIcon from 'icons/InformationOutline'
import SuccessIcon from 'icons/CheckCircle'
import WarnIcon from 'icons/Alert'

export default {
	name: "Notification",
  components: {
    ErrorIcon,
    InfoIcon,
    SuccessIcon,
    WarnIcon
  }
}
</script>

<style scoped>
</style>
