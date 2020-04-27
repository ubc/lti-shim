<template>
  <notifications position="top center" :duration='5000' class='w-auto'>
    <template slot="body" slot-scope="props">
      <div class='alert mt-1'
           :class="{
             'alert-primary': !props.item.type,
             'alert-success': props.item.type == 'success',
             'alert-danger': props.item.type == 'error',
             'alert-warning': props.item.type == 'warn',
           }">
        <button type="button" class="close" data-dismiss="alert" 
          aria-label="Close" @click="props.close">
          <CloseIcon />
        </button>
        <h5 class='mb-0'>
          <InfoIcon v-if="!props.item.type" />
          <SuccessIcon v-if="props.item.type == 'success'" />
          <ErrorIcon v-if="props.item.type == 'error'" />
          <WarnIcon v-if="props.item.type == 'warn'" />
          <strong class='ml-1'>
            {{ props.item.title }}
          </strong>
        </h5>
        <div v-if='props.item.text' v-html='props.item.text'>
        </div>
      </div>
    </template>
  </notifications>
</template>

<script>
import CloseIcon from 'icons/CloseCircle'
import ErrorIcon from 'icons/Alert'
import InfoIcon from 'icons/InformationOutline'
import SuccessIcon from 'icons/CheckCircle'
import WarnIcon from 'icons/Alert'

export default {
	name: "Notification",
  components: {
    CloseIcon,
    ErrorIcon,
    InfoIcon,
    SuccessIcon,
    WarnIcon
  }
}
</script>

<style scoped>
</style>
