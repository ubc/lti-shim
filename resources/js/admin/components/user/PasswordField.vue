<template>
	<div class='plainForm'>
		<label for='password'>Password</label>
    <div class='flex gap-2'>
      <input id='password' name='password'
        class='grow'
        spellcheck='false'
        v-bind:type='passwordType' 
        v-bind:autocomplete='passwordAutocomplete' 
        v-bind:value='password'
        v-bind:required='isRequired'
        v-bind:disabled='isDisabled'
        v-bind:minlength='minlength'
        v-on:input='onPasswordInput'
        />
      <div class='flex-initial self-center'>
        <button type='button' @click='togglePasswordVisible'
          class='btnSecondary btnSm mt-0' >
          <EyeOffIcon v-if='showPassword' />
          <EyeIcon v-else />
          {{ showPassword ? 'hide' : 'show' }} password
        </button>
      </div>
    </div>
	</div>
</template>

<script>
import EyeIcon from 'icons/Eye'
import EyeOffIcon from 'icons/EyeOff'

export default {
	name: "PasswordField",
	components: {
		EyeIcon,
		EyeOffIcon
	},
	computed: {
		passwordAutocomplete() {
			if (this.isNewPassword) return 'new-password'
			return 'current-password'
		}
	},
	data() { return {
		passwordType: 'password',
		password: '',
		showPassword: false
	}},
	props: {
		isNewPassword: Boolean,
		isRequired: {
			type: Boolean,
			default: false
		},
		isDisabled: {
			type: Boolean,
			default: false
		},
    minlength: {
      type: Number,
      default: 8
    }
	},
	methods: {
		onPasswordInput(event) {
			this.$emit("input", event.target.value)
			this.password = event.target.value
		},
		togglePasswordVisible() {
			this.passwordType = this.passwordType == 'password' ? 'text' : 'password'
			this.showPassword = !this.showPassword
		}
	}
}
</script>

<style scoped>
</style>
