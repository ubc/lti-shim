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
    public const ALG = 'alg';
    public const RS256 = 'RS256';
    public const KID = 'kid';
    public const SUB = 'sub';
    public const AUD = 'aud';
    public const EXP = 'exp';
    public const IAT = 'iat';
    public const AZP = 'azp';
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
}
