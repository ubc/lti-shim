## Structure

* Filters: Filters are how we re-write LTI data for anonymization and such. Each spec has their own set of filters. Some filters in different specs are similar in function and can have duplicated code. We can pull out those shared elements into abstract classes stored in this directory.
* Specs: Since LTI has been split up into multiple specs for different functions, we can roughly structure the code by spec. For each spec, we can further divide them into Platform and Tool side functionalities.
  * Launch: Many specs extend the launch, but it is primarily defined by the core spec
    * [LTI 1.3 Core Spec](https://www.imsglobal.org/spec/lti/v1p3/)
    * Gradebook Message: Companion spec to AGS
    * Deep Link (currently a separate implementation but should be merged into launch proper)
  * Core: Parts of the Core spec that isn't handled by the Launch
  * Security: [IMS Security Framework](https://www.imsglobal.org/spec/security/v1p0) - Mainly OAuth2 related implementations.
  * Nrps: [Names and Role Provisioning Services](http://www.imsglobal.org/spec/lti-nrps/v2p0)
  * Ags: [Assignment and Grade Services](http://www.imsglobal.org/spec/lti-ags/v2p0)
  * DeepLink: [Deep Linking](https://www.imsglobal.org/spec/lti-dl/v2p0)

