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
      <h4>Add shim to a platform</h4>
      <p>When adding the shim to an LTI Platform (e.g. Canvas). Choose the target tool the user should end up in.</p>

      <form class='form-inline mb-3'>
        <label for='targetToolSelect' class='mr-2'>Target Tool</label>
        <select v-model='targetTool' id='targetToolSelect'
          class='custom-select'>
          <option v-for='tool in tools' :value='tool'>
          {{ tool.name }}
          </option>
        </select>
      </form>

      <div class='alert alert-primary'
        v-if="!targetTool.hasOwnProperty('id')">
        Please select a target tool
      </div>

      <table class='table' v-else="targetTool.hasOwnProperty('id')">
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
            <th scope="row">Target Link URI</th>
            <td>{{targetTool.shim_target_link_uri}}</td>
          </tr>
          <tr>
            <th scope="row">JWKS URL</th>
            <td>{{config.tool.jwks_url}}</td>
          </tr>
        </tbody>
      </table>


      <h4>Add shim to a tool</h4>
      <p>When adding the shim to an LTI Tool (e.g. Webwork). Choose the tool
      you're configuring.</p>

      <form class='form-inline mb-3'>
        <label for='selectedToolSelect' class='mr-2'>Tool</label>
        <select v-model='selectedTool' id='selectedToolSelect'
          class='custom-select'>
          <option v-for='tool in tools' :value='tool'>
          {{ tool.name }}
          </option>
        </select>
      </form>

      <div class='alert alert-primary'
        v-if="!selectedTool.hasOwnProperty('id')">
        Please select a tool
      </div>

      <table class='table' v-if="selectedTool.hasOwnProperty('id')">
        <tbody>
          <tr>
            <th scope="row">ISS</th>
            <td>{{config.platform.iss}}</td>
          </tr>
          <tr>
            <th scope="row">Client ID</th>
            <td>{{selectedTool.client_id}}</td>
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
