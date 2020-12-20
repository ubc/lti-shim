<?php
namespace UBC\LTI\Utils;

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
    public const PERSON_SOURCEDID = 'person_sourcedid';
    // course context claims
    public const LABEL = 'label';
    public const TITLE = 'title';
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
    public const LIS_URI =
        'https://purl.imsglobal.org/spec/lti/claim/lis';
    // LTI message types
    public const MESSAGE_TYPE_RESOURCE_LINK = 'LtiResourceLinkRequest';
    public const MESSAGE_TYPE_GRADEBOOK = 'LtiSubmissionReviewRequest';
    public const MESSAGE_TYPES = [
        self::MESSAGE_TYPE_RESOURCE_LINK,
        self::MESSAGE_TYPE_GRADEBOOK
    ];
    // LTI service oauth token request
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
    public const BEARER_PREFIX = 'Bearer ';

    // Names and Roles Provisioning Service (NRPS) response params
    public const NRPS = 'nrps';
    public const ID = 'id';
    public const CONTEXT = 'context';
    public const MEMBERS = 'members';
    public const USER_ID = 'user_id';
    public const STATUS = 'status';
    public const ROLES = 'roles';
    public const LINK = 'link';
    public const LIS_PERSON_SOURCEDID = 'lis_person_sourcedid';
    // NRPS get params
    public const ROLE = 'role';
    public const LIMIT = 'limit';
    // NRPS uri
    public const NRPS_CLAIM_URI =
        'https://purl.imsglobal.org/spec/lti-nrps/claim/namesroleservice';
    // oauth token scope
    public const NRPS_SCOPE_URI =
        'https://purl.imsglobal.org/spec/lti-nrps/scope/contextmembership.readonly';
    public const CONTEXT_MEMBERSHIPS_URL = 'context_memberships_url';
    public const SERVICE_VERSIONS = 'service_versions';
    public const NRPS_MEDIA_TYPE =
        'application/vnd.ims.lti-nrps.v2.membershipcontainer+json';

    // Assignment and Grades Service (AGS)
    public const AGS = 'ags';
    public const AGS_CLAIM_URI =
        'https://purl.imsglobal.org/spec/lti-ags/claim/endpoint';
    public const AGS_SCOPE_LINEITEM_URI =
        'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem';
    public const AGS_SCOPE_LINEITEM_READONLY_URI =
        'https://purl.imsglobal.org/spec/lti-ags/scope/lineitem.readonly';
    public const AGS_SCOPE_RESULT_READONLY_URI =
        'https://purl.imsglobal.org/spec/lti-ags/scope/result.readonly';
    public const AGS_SCOPE_SCORE_URI =
        'https://purl.imsglobal.org/spec/lti-ags/scope/score';
    public const AGS_LINEITEM = 'lineitem';
    public const AGS_LINEITEMS = 'lineitems';
    public const AGS_MEDIA_TYPE_LINEITEM =
        'application/vnd.ims.lis.v2.lineitem+json';
    public const AGS_MEDIA_TYPE_LINEITEMS =
        'application/vnd.ims.lis.v2.lineitemcontainer+json';
    public const AGS_MEDIA_TYPE_RESULTS =
        'application/vnd.ims.lis.v2.resultcontainer+json';
    public const AGS_MEDIA_TYPE_SCORE =
        'application/vnd.ims.lis.v1.score+json';
    public const AGS_RESULTS_PATH = 'results';
    public const AGS_SCORES_PATH = 'scores';
    // ags lineitem query params
    public const RESOURCE_LINK_ID = 'resource_link_id';
    public const RESOURCE_ID = 'resource_id';
    public const TAG = 'tag';
    // ags result params
    public const SCORE_OF = 'scoreOf';
    public const AGS_USER_ID = 'userId';
    // ags score
    public const RESULT_URL = 'resultUrl';

    // List of scopes that can be used to request access tokens
    public const AGS_SCOPES = [
        self::AGS_SCOPE_LINEITEM_URI => self::AGS . '1',
        self::AGS_SCOPE_LINEITEM_READONLY_URI => self::AGS . '2',
        self::AGS_SCOPE_RESULT_READONLY_URI => self::AGS . '3',
        self::AGS_SCOPE_SCORE_URI => self::AGS . '4'
    ];
    public const NRPS_SCOPES = [self::NRPS_SCOPE_URI => self::NRPS . '1'];
}
