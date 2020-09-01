<template>
  <div class='card'>
    <h2 class='card-header'>Shim Configuration Info</h2>
    <div class='card-body' v-if='isLoading'>
      Loading...
    </div>
    <div class='card-body' v-else>
      <p>
      When you need to add the shim to other LTI platforms or tools, use these
      configuration information.
      </p>
      <h4>Shim as an LTI Platform</h4>
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
      <h4>Shim as an LTI Tool</h4>
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
