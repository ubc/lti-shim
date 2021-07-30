<template>
  <div>
    <h2>Shim Configuration Info</h2>
    <div v-if='isLoading'>
      Loading...
    </div>
    <div v-else>
      <p>
      Information to help configure LTI platforms or tools to use the shim.
      </p>
      <h3>Add shim to a platform</h3>
      <p>When adding the shim to an LTI Platform (e.g. Canvas). Choose the target tool the user should end up in.</p>

      <p class='notifyWarning' v-if="!targetTool.hasOwnProperty('id')">
      Please select a target tool to view target specific parameters.
      </p>

      <form class='mb-4'>
        <label for='targetToolSelect' class='mr-2'>Target Tool</label>
        <select v-model='targetTool' id='targetToolSelect'>
          <option v-for='tool in tools' :value='tool'>
          {{ tool.name }}
          </option>
        </select>
      </form>

      <table class='definitionTable'>
        <tbody>
          <tr>
            <th scope="row">OIDC Login URL</th>
            <td>{{config.tool.oidc_login_url}}</td>
          </tr>
          <tr>
            <th scope="row">Auth Response URL</th>
            <td>{{config.tool.auth_resp_url}}</td>
          </tr>
          <tr>
            <th scope="row">JWKS URL</th>
            <td>{{config.tool.jwks_url}}</td>
          </tr>
          <tr>
            <th scope="row">Target Link URI</th>
            <td>
              <span class='textWarning' v-if="!targetTool.hasOwnProperty('id')">
              No target tool selected!
              </span>
              {{targetTool.shim_target_link_uri}}
            </td>
          </tr>
        </tbody>
      </table>

      <h3 class='mt-4'>Add shim to a tool</h3>
      <p>When adding the shim to an LTI Tool (e.g. Webwork).</p>

      <p class='notifyWarning' v-if="!selectedTool.hasOwnProperty('id')">
      Please select a tool to view tool specific parameters.
      </p>

      <form class='mb-4'>
        <label for='selectedToolSelect' class='mr-2'>Tool</label>
        <select v-model='selectedTool' id='selectedToolSelect'>
          <option v-for='tool in tools' :value='tool'>
          {{ tool.name }}
          </option>
        </select>
      </form>

      <table class='definitionTable'>
        <tbody>
          <tr>
            <th scope="row">ISS</th>
            <td>{{config.platform.iss}}</td>
          </tr>
          <tr>
            <th scope="row">Auth Request URL</th>
            <td>{{config.platform.auth_req_url}}</td>
          </tr>
          <tr>
            <th scope="row">JWKS URL</th>
            <td>{{config.platform.jwks_url}}</td>
          </tr>
          <tr>
            <th scope="row">Access Token URL</th>
            <td>{{config.platform.access_token_url}}</td>
          </tr>
          <tr>
            <th scope="row">Client ID</th>
            <td>
              <span class='textWarning'
                v-if="!selectedTool.hasOwnProperty('id')">
                No tool selected!
              </span>
              {{selectedTool.client_id}}
            </td>
          </tr>
        </tbody>
      </table>

    </div>
  </div>
</template>

<script>
export default {
  name: "ShimConfigInfo",
  computed: {
    tools() { return this.$store.state.tool.items }
  },
  data() { return {
    config: {},
    isLoading: true,
    selectedTool: {},
    targetTool: {}
  }},
  mounted() {
    this.isLoading = true
    axios.get('/api/help/config')
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
}
</script>

<style scoped>
.definitionTable {
  @apply py-2 mb-2;
  th { @apply text-right; }
  th, td { @apply px-2 py-1; }
}
</style>
