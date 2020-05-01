<template>
	<div class='card'>
		<h2 class='card-header'>Shim Configuration Info</h2>
    <div class='card-body'>
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
        </tbody>
      </table>
      <h4>Shim as an LTI Tool</h4>
      <p>When adding the shim to an LTI Platform (e.g. Canvas).</p>
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
            <th scope="row">Target Link URI</th>
            <td>{{config.tool.target_link_uri}}</td>
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
  data() { return {
    config: {}
  }},
  mounted() {
    axios.get('/api/help/config')
      .then(response => {
        this.config = response.data
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
