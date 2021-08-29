<template>
  <div>
    <h3>Step-by-Step</h3>
    <p>
    Given a platform and tool pair, the shim works to proxy data between the
    two, filtering private user information as necessary. To add a new platform
    and tool pair, follow this step-by-step instruction.
    </p>

    <h4>1. Select platform</h4>
    <p class='notifyError' v-if="!selectedPlatform.hasOwnProperty('id')">
    Please select a platform.
    </p>
    <form class='mb-4'>
      <label for='selectedPlatformSelect' class='mr-2'>Platform</label>
      <select v-model='selectedPlatformId' id='selectedPlatformSelect'
        @change='getConfig'>
        <option v-for='platform in platforms' :value='platform.id'>
        {{ platform.name }}
        </option>
      </select>
    </form>
    <p class='mb-8'>
    If the platform you're using is not in the list, add it on the
    <router-link :to="{name: 'adminPlatform'}">Platforms</router-link>
    page and then return here.
    </p>

    <h4>2. Select tool</h4>
    <p class='notifyError' v-if="!selectedTool.hasOwnProperty('id')">
    Please select a tool.
    </p>
    <form class='mb-4'>
      <label for='selectedToolSelect' class='mr-2'>Tool</label>
      <select v-model='selectedToolId' id='selectedToolSelect'
        @change='getConfig'>
        <option v-for='tool in tools' :value='tool.id'>
        {{ tool.name }}
        </option>
      </select>
    </form>
    <p class='mb-8'>
    If the tool you're using is not in the list, add it on the
    <router-link :to="{name: 'adminTool'}">Tools</router-link>
    page and then return here.
    </p>

    <h4>
      3. Go to the platform
      <span v-if='"name" in selectedPlatform'>
        ({{ selectedPlatform.name }})
      </span>
      and add the shim as an LTI tool
    </h4>
    <p>
    For example, to add an LTI tool in Canvas, you go to Admin's "Developer
    Keys".
    </p>
    <p>
    Please use the provided parameters that follows. Note that this
    configuration is customized to take the user to the selected
    tool<span v-if='"name" in selectedTool'> ({{ selectedTool.name }})</span>.
    To take the user to other tools, you must create a separate LTI tool entry
    on the platform for each of those tools.
    </p>
    <table class='definitionTable' v-if='"tool" in config'>
      <tbody>
        <tr>
          <th scope="row">OIDC Login URL</th>
          <td>{{config.tool.loginUrl}}</td>
        </tr>
        <tr>
          <th scope="row">Redirect URL</th>
          <td>{{config.tool.redirectUrl}}</td>
        </tr>
        <tr>
          <th scope="row">JWKS URL</th>
          <td>{{config.tool.jwksUrl}}</td>
        </tr>
        <tr>
          <th scope="row">Target Link URI</th>
          <td>{{config.tool.targetLinkUrl}}</td>
        </tr>
      </tbody>
    </table>
    <p class='notifyWarning' v-else>
    Select a platform and tool to see parameters.
    </p>

    <h4>
      4. Come back to shim admin and configure the selected platform's
      Allowed Tools.
    </h4>
    <p>
    From the previous step, the platform should have given you a client ID. You
    need to enter this client ID under Allowed Tools. The client ID is paired
    with the selected tool
    <span v-if='"name" in selectedTool'>({{ selectedTool.name }})</span> and
    is necessary for doing LTI service calls.
    </p>
    <div v-if='"name" in selectedPlatform'>
      <h5>{{ selectedPlatform.name }}</h5>
      <PlatformClientAdmin :platformId='selectedPlatform.id' />
    </div>
    <p class='notifyWarning' v-else>
    Select a platform to show allowed tools.
    </p>

    <h4>
      5. Go to the tool
      <span v-if='"name" in selectedTool'>({{ selectedTool.name}})</span>
      and add the shim as an LTI platform
    </h4>
    <p>
    Please use the provided parameters.
    </p>
    <table class='definitionTable' v-if='"platform" in config'>
      <tbody>
        <tr>
          <th scope="row">ISS</th>
          <td>{{config.platform.iss}}</td>
        </tr>
        <tr>
          <th scope="row">Auth URL</th>
          <td>{{config.platform.authUrl}}</td>
        </tr>
        <tr>
          <th scope="row">JWKS URL</th>
          <td>{{config.platform.jwksUrl}}</td>
        </tr>
        <tr>
          <th scope="row">Access Token URL</th>
          <td>{{config.platform.tokenUrl}}</td>
        </tr>
        <tr>
          <th scope="row">Client ID</th>
          <td>{{selectedTool.client_id}}</td>
        </tr>
      </tbody>
    </table>
    <p class='notifyWarning' v-else>
    Select a platform and tool to see parameters.
    </p>

    </div>
  </div>
</template>

<script>
import AddIcon from 'icons/Plus'

import PlatformClientAdmin from '@admin/components/platformClient/PlatformClientAdmin'
import PlatformForm from '@admin/components/platform/PlatformForm'

export default {
  name: "ShimConfigInfo",
  components: {
    AddIcon,
    PlatformClientAdmin,
    PlatformForm,
  },
  computed: {
    selectedTool() {
      if (this.selectedToolId == 0) return {}
      return this.$store.state.tool.items[this.selectedToolId]
    },
    selectedPlatform() {
      if (this.selectedPlatformId == 0) return {}
      return this.$store.state.platform.items[this.selectedPlatformId]
    },
    tools() { return this.$store.state.tool.items },
    platforms() { return this.$store.state.platform.items }
  },
  data() { return {
    config: {},
    isLoading: false,
    selectedToolId: 0,
    selectedPlatformId: 0,
    targetTool: {}, // TODO delete
  }},
  methods: {
    getConfig() {
      if (this.selectedPlatformId > 0)
        this.$store.commit('setSelectedPlatformId', this.selectedPlatformId)
      if (this.selectedToolId > 0)
        this.$store.commit('setSelectedToolId', this.selectedToolId)
      if (this.selectedToolId == 0 || this.selectedPlatformId == 0)
        return
      let url = '/api/help/config/platform/' + this.selectedPlatform.id +
                '/tool/' + this.selectedTool.id
      this.isLoading = true
      axios.get(url)
        .then(response => {
          this.config = response.data
          this.isLoading = false
        })
        .catch(error => {
          this.$notify({
            'title': 'Failed to get shim config info.',
            'type': 'error'
          });
        })
    }
  },
  mounted() {
    this.$store.dispatch('platform/getAll')
    this.$store.dispatch('tool/getAll')
    if (this.$store.state.selectedPlatformId > 0)
      this.selectedPlatformId = this.$store.state.selectedPlatformId
    if (this.$store.state.selectedToolId > 0)
      this.selectedToolId = this.$store.state.selectedToolId
  }
}
</script>

<style scoped>
.definitionTable {
  @apply py-2 mb-2;
  th { @apply text-right; }
  th, td { @apply px-2 py-1; }
}
h4 {
  @apply mt-8;
}
</style>
