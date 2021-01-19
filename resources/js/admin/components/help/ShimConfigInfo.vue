<template>
  <div class='card'>
    <h2 class='card-header'>Shim Configuration Info</h2>
    <div class='card-body' v-if='isLoading'>
      Loading...
    </div>
    <div class='card-body' v-else>
      <p>
      Information to help configure LTI platforms or tools to use the shim.
      </p>
      <h3>Add shim to a platform</h3>
      <p>When adding the shim to an LTI Platform (e.g. Canvas). Choose the target tool the user should end up in.</p>

      <table class='table'>
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
        </tbody>
      </table>

      <p class='alert alert-warning'>
      A target link URI is also needed, but it is tool specific, please select
      a target tool to view the correct target link URI.
      </p>

      <form class='form-inline mb-3'>
        <label for='targetToolSelect' class='mr-2'>Target Tool</label>
        <select v-model='targetTool' id='targetToolSelect'
          class='custom-select'>
          <option v-for='tool in tools' :value='tool'>
          {{ tool.name }}
          </option>
        </select>
      </form>

      <p class='text-muted'
        v-if="!targetTool.hasOwnProperty('id')">
        No target tool selected!
      </p>

      <table class='table' v-else="targetTool.hasOwnProperty('id')">
        <tbody>
          <tr>
            <th scope="row">Target Link URI</th>
            <td>{{targetTool.shim_target_link_uri}}</td>
          </tr>
        </tbody>
      </table>


      <h3>Add shim to a tool</h3>
      <p>When adding the shim to an LTI Tool (e.g. Webwork).</p>

      <table class='table'>
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
        </tbody>
      </table>

      <p class='alert alert-warning'>
      A client ID is also needed. This is assigned when the tool was added to
      the shim. If the tool hasn't been added to the shim, please add it now.
      Select an existing tool here to view the client ID.
      </p>

      <form class='form-inline mb-3'>
        <label for='selectedToolSelect' class='mr-2'>Tool</label>
        <select v-model='selectedTool' id='selectedToolSelect'
          class='custom-select'>
          <option v-for='tool in tools' :value='tool'>
          {{ tool.name }}
          </option>
        </select>
      </form>

      <p class='text-muted'
        v-if="!selectedTool.hasOwnProperty('id')">
        No tool selected!
      </p>
      <table class='table' v-if="selectedTool.hasOwnProperty('id')">
        <tbody>
          <tr>
            <th scope="row">Client ID</th>
            <td>{{selectedTool.client_id}}</td>
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
</style>
