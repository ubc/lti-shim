<template>
  <div class='mx-auto p-2'>
    <h1 class='py-4'>
      Select Your Anonymization Option for {{ toolName }}
    </h1>
    <p class='mb-4'>
    {{ toolName }} is hosted outside of Canada and would like access to your
    student data from Canvas. To protect your privacy, we have generated a fake
    identity for this data sharing. Note that this fake identity will not
    affect your instructor's ability to grade and identify you, as they are
    able to map fake identities to real identities.
    </p>
    <p>
    If you wish, you can choose to share your real identity with {{ toolName
    }}.  <span class='textWarning'>Warning, this is irreversible!</span> You
    cannot go back to an anonymized identity.
    </p>

    <form @submit.prevent='confirmAnonymizationOption' class='m-8'>
      <label class='anonymizationRadio'
             v-bind:class="{ 'bg-ubcblue-100': isAnonymized }">
        <input type='radio' name='isAnonymized'
               :disabled='isWaiting || hasSelectedAnonymizationOption'
               :value='true' v-model='isAnonymized' />
        Use My Anonymized Identity (Recommended)
      </label>
      <label class='anonymizationRadio'
             v-bind:class="{ 'bg-ubcblue-100': !isAnonymized }">
        <input type='radio' name='isAnonymized'
               :disabled='isWaiting || hasSelectedAnonymizationOption'
               :value='false' v-model='isAnonymized' />
        Use My Real Identity
      </label>

      <button type='submit' class='btnPrimary text-base p-2 mt-2'
        :disabled='isWaiting || hasSelectedAnonymizationOption'>
        <ConfirmIcon /> Confirm Anonymization Option
      </button>
      <LoadingIcon class='animate-pulse text-ubcblue-500' role='status'
                   aria-hidden='true' v-if='isWaiting' />

    </form>

    <p class='notifyError' v-if='errorMsg'>
    <ErrorIcon class='notifyIcon' />
    Failed to save anonymization option:
    {{ errorMsg }}
    </p>

    <p v-if='hasSelectedAnonymizationOption && isAnonymized'
       class='notifyInfo'>
    <NotifyIcon class='notifyIcon' />
    You have selected to maintain an anonymized identity.
    </p>
    <p v-if='hasSelectedAnonymizationOption && !isAnonymized'
       class='notifyInfo'>
    <NotifyIcon class='notifyIcon' />
    You have selected to use your real identity.
    </p>

    <ContinuationForm v-if='hasSelectedAnonymizationOption'
                      :action='action' :tool-name='toolName'
                      :continuation-id-token='continuationIdToken'
                      :continuation-state='continuationState'
                      :is-midway-only='isMidwayOnly' />
    <p v-if='hasSelectedAnonymizationOption && isMidwayOnly'>
    Please re-launch {{ toolName }} from Canvas.
    </p>
  </div>
</template>

<script>
import ConfirmIcon from 'icons/CheckBold'
import ErrorIcon from 'icons/AlertOctagon'
import LoadingIcon from 'icons/TimerSandFull'
import NotifyIcon from 'icons/InformationOutline'

import ContinuationForm from '@midway/components/ContinuationForm'

export default {
  name: "FirstTimeSetupView",
  components: {
    ConfirmIcon,
    ContinuationForm,
    ErrorIcon,
    LoadingIcon,
    NotifyIcon,
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
    fakeUserId: {
      type: Number,
      required: true
    },
    isMidwayOnly: {
      type: Boolean,
      required: true
    },
    token: {
      type: String,
      required: true
    },
    toolName: {
      type: String,
      required: true
    },
  },
  data() { return {
    errorMsg: '',
    isAnonymized: true,
    isWaiting: false,
    hasSelectedAnonymizationOption: false
  }},
  methods: {
    confirmAnonymizationOption() {
      this.isWaiting = true
      this.errorMsg = ''
      let url = '/api/midway/config/anonymization/' + this.fakeUserId
      axios.post(url, {'is_anonymized': this.isAnonymized})
        .then(response => {
          this.isWaiting = false
          this.hasSelectedAnonymizationOption = true
        })
        .catch(response => {
          this.isWaiting = false
          this.errorMsg = response.response.data
        })
    }
  },
  mounted() {
  },
  created() {
    // Initialization needs to be in created() cause mounted() is too late.
    // Child components will make api calls before our mounted() is called.
    axios.defaults.headers.common['Authorization'] = 'Bearer ' + this.token
  }
}
</script>

<style scoped>
.anonymizationRadio {
  @apply my-2 p-4 block;
  input { @apply mr-2; }
}
.notifyIcon {
  @apply text-xl;
}
</style>
