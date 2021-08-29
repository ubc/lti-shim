<template>
  <div>
    <h4>LTI Dictionary</h4>
    <dl>
      <dt>Platform</dt>
      <dd>
      LTI platforms are usually Learning Managment Systems (e.g.: Canvas).
      Users start in the platform are launched into tools. During the launch,
      platforms send the user's personal information to the tool. For the shim,
      the platform stores the real user information.
      </dd>
      <dt>Tool</dt>
      <dd>
      LTI tools are usually specialized learning apps (e.g.: Blogs, Webwork,
      iPeer) that receive launches. The shim sends anonymized fake user
      information to tools. Tools may send or request information from the
      platform through LTI services.
      </dd>
      <dt>Launch</dt>
      <dd>
      An LTI launch sends a user from a platform to a tool. This acts as an
      authentication layer, the user's account on the platform is used to
      create/login to an account on the tool. The shim acts a proxy relay for
      these LTI launches, where it replaces real personal information with a
      fake randomly generated identity.
      </dd>
      <dt>Service</dt>
      <dd>
      LTI services is the reverse of an LTI launch. A launch goes from platform
      to tool. A service call is from tool to platform. Tools may send
      information, such as a user's grade on an assignment, back to the
      platform.  Tools can also request information, such as the class roster,
      from the platform.
      </dd>
    </dl>
  </div>
</template>

<script>
export default {
  name: "LtiDictionary",
  components: {
  },
  data() { return {
  }},
  mounted() {
  }
}
</script>

<style scoped>
dl {
  @apply mb-4 ml-4;

  dt {
    @apply font-bold;
  }
  dd {
    @apply mb-2 ml-4;
  }
}
</style>
