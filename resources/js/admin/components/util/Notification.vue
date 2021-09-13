<template>
  <notifications position="top center" :duration='5000'>
    <template slot="body" slot-scope="props">
      <div class='mt-2 flex justify-between'
           :class="{
             'notifyInfo': !props.item.type,
             'notifySuccess': props.item.type == 'success',
             'notifyError': props.item.type == 'error',
             'notifyWarning': props.item.type == 'warn',
           }">

        <div>
          <h5 class='mb-0'>
            <InfoIcon v-if="!props.item.type" class='notifyIcon' />
            <SuccessIcon v-if="props.item.type == 'success'"
                         class='notifyIcon' />
            <ErrorIcon v-if="props.item.type == 'error'" class='notifyIcon' />
            <WarnIcon v-if="props.item.type == 'warn'" class='notifyIcon' />
            <strong class='ml-1'>
              {{ props.item.title }}
            </strong>
          </h5>
          <div v-if='props.item.text' v-html='props.item.text'>
          </div>
        </div>

        <button type="button" class="notifyClose" data-dismiss="alert" 
          aria-label="Close" @click="props.close">
          <CloseIcon />
        </button>

      </div>
    </template>
  </notifications>
</template>

<script>
import CloseIcon from 'icons/CloseCircle'
import ErrorIcon from 'icons/AlertOctagon'
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
