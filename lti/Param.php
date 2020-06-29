<?php
namespace UBC\LTI;

class Param
{
    // oidc login
    public const ISS = 'iss';
    public const LOGIN_HINT = 'login_hint';
    public const TARGET_LINK_URI = 'target_link_uri';
    public const CLIENT_ID = 'client_id';
    public const LTI_MESSAGE_HINT = 'lti_message_hint';
    public const LTI_DEPLOYMENT_ID = 'lti_deployment_id';
    // auth request
    public const SCOPE = 'scope';
    public const OPENID = 'openid';
    public const RESPONSE_TYPE = 'response_type';
    public const RESPONSE_MODE = 'response_mode';
    public const FORM_POST = 'form_post';
    public const REDIRECT_URI = 'redirect_uri';
    public const STATE = 'state';
    public const NONCE = 'nonce';
    public const PROMPT = 'prompt';
    public const NONE = 'none';
    // auth resp
    public const ID_TOKEN = 'id_token';
    // id_token jwt
    public const TYP = 'typ';
    public const JWT = 'JWT';
    public const ALG = 'alg';
    public const RS256 = 'RS256';
    public const KID = 'kid'; // key id
    public const SUB = 'sub';
    public const AUD = 'aud';
    public const EXP = 'exp'; // timestamp, expires on
    public const IAT = 'iat'; // timestamp, issued at
    public const AZP = 'azp';
    public const NBF = 'nbf'; // timestamp, not before
    // encrypted jwt used for state & access tokens
    public const AT_JWT = 'at+JWT'; // typ value for access tokens
    public const RSA_OAEP_256 = 'RSA-OAEP-256';
    public const A256GCM = 'A256GCM';
    public const ZIP_ALG = 'DEF'; // DEFLATE alg for zip compression
    // non-URI claims
    public const PICTURE = 'picture'; // avatar link
    public const GIVEN_NAME = 'given_name';
    public const FAMILY_NAME = 'family_name';
    public const NAME = 'name';
    public const EMAIL = 'email';
    // LTI defined URIs
    public const MESSAGE_TYPE_URI =
        'https://purl.imsglobal.org/spec/lti/claim/message_type';
    public const VERSION_URI =
        'https://purl.imsglobal.org/spec/lti/claim/version';
    public const DEPLOYMENT_ID_URI =
        'https://purl.imsglobal.org/spec/lti/claim/deployment_id';
    public const TARGET_LINK_URI_URI =
        'https://purl.imsglobal.org/spec/lti/claim/target_link_uri';
    public const RESOURCE_LINK_URI =
        'https://purl.imsglobal.org/spec/lti/claim/resource_link';
    public const ROLES_URI =
        'https://purl.imsglobal.org/spec/lti/claim/roles';
    public const CUSTOM_URI =
        'https://purl.imsglobal.org/spec/lti/claim/custom';
    public const LAUNCH_PRESENTATION_URI =
        'https://purl.imsglobal.org/spec/lti/claim/launch_presentation';
    public const CONTEXT_URI =
        'https://purl.imsglobal.org/spec/lti/claim/context';
    // lti service oauth token request
    public const GRANT_TYPE = 'grant_type';
    public const GRANT_TYPE_VALUE = 'client_credentials';
    public const CLIENT_ASSERTION = 'client_assertion';
    public const CLIENT_ASSERTION_TYPE = 'client_assertion_type';
    public const CLIENT_ASSERTION_TYPE_VALUE =
        'urn:ietf:params:oauth:client-assertion-type:jwt-bearer';
    public const ACCESS_TOKEN = 'access_token';
    public const TOKEN_TYPE = 'token_type';
    public const TOKEN_TYPE_VALUE = 'bearer';
    public const EXPIRES_IN = 'expires_in';
    // Names and Roles Provisioning Service
    public const ID = 'id';
    public const CONTEXT = 'context';
    public const MEMBERS = 'members';
    public const NRPS_CLAIM_URI =
        'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';
    // oauth token scope
    public const NRPS_SCOPE_URI =
        'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
    public const CONTEXT_MEMBERSHIPS_URL = 'context_memberships_url';
    public const SERVICE_VERSIONS = 'service_versions';
}
