<?php
namespace UBC\LTI\Specs;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use UBC\LTI\LtiException;
use UBC\LTI\Param;

// Parses, validates, constructs Role URIs as defined by the LIS spec
class RoleVocabulary
{
    // LIS vocabulary for course roles are all URIs, instead of hardcoding each
    // role's URI, I'm splitting up into components that can be assembled as
    // needed. This works out better since sub-roles require something like it
    // anyways.
    public const ROLE_PREFIX = 'http://purl.imsglobal.org/vocab/lis/v2/';
    // there are 3 role types
    public const ROLE_TYPE_SYSTEM = 'system';
    public const ROLE_TYPE_INSTITUTION = 'institution';
    public const ROLE_TYPE_MEMBERSHIP = 'membership';
    // system and institution roles use person in their path
    public const ROLE_PERSON = 'person';
    // these are considered primary roles
    public const ROLE_ADMINISTRATOR = 'Administrator';
    public const ROLE_CONTENT_DEVELOPER = 'ContentDeveloper';
    public const ROLE_INSTRUCTOR = 'Instructor';
    public const ROLE_LEARNER = 'Learner';
    public const ROLE_MANAGER = 'Manager';
    public const ROLE_MEMBER = 'Member';
    public const ROLE_MENTOR = 'Mentor';
    public const ROLE_OFFICER = 'Officer';
    // these are sub roles underneath one of the primary roles
    public const SUBROLE_LECTURER = 'Lecturer';
    public const SUBROLE_PRIMARY_INSTRUCTOR = 'PrimaryInstructor';
    public const SUBROLE_SECONDARY_INSTRUCTOR = 'SecondaryInstructor';

    private static string $institutionAdminRole = '';
    private static array $instructorRoles = [];

    public function __construct()
    {
        self::initStaticRoles();
    }

    /**
     * Given a list of roles as passed in the LTI launch's role URI claim,
     * return true if the user is allowed to look up real user identities, false
     * otherwise.
     *
     * We're only allowing institution admins and course instructors for now.
     * Checking for instructors is a bit more involved, we only look at
     * membership roles and need to make sure that if they give us subroles, it
     * matches those known to be instructors. We need to be careful of the case
     * where the user's primary role is instructor but has the subrole teaching
     * assistant, this user needs shouldn't be allowed lookup.
     *
     * @param array $roles
     */
    public function canLookupRealUsers(array $roles): bool
    {
        // check for admin first, as admin role override membership roles
        foreach ($roles as $role) {
            if ($role == self::$institutionAdminRole) return true;
        }
        // limit to membership roles
        $membershipPrefix = self::ROLE_PREFIX . self::ROLE_TYPE_MEMBERSHIP;
        $ret = false; // defaults to false if $roles empty
        foreach ($roles as $role) {
            if (!Str::startsWith($role, $membershipPrefix)) continue;
            if (isset(self::$instructorRoles[$role])) {
                $ret = true;
            }
            else {
                $ret = false;
                break;
            }
        }
        return $ret;
    }

    /**
     * The possible combinations for the Role URI is large enough that I don't
     * want to hardcode them all manually, so this method builds them for us.
     *
     * All of the Role URI has the same prefix.
     *
     * The role type is given in the path. For System and Institution role
     * types, a '/person' is added to the path. The primary role is given in
     * the hash part of the URI. Some examples:
     * http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator
     * http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator
     * http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator
     *
     * If a subrole is present, the primary role gets turned into part of the
     * path and the subrole is given in the hash, example:
     * http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant
     *
     * I'm not sure if subroles are allowed for the System and
     * Institution role types. The format given in the core spec specifies only
     * membership, but it wouldn't be the first time that the examples in the
     * spec are outright wrong. I've allowed subroles for all role types since
     * that makes more sense anyways.
     *
     * Note that this does NOT check if the given role type allows the given
     * role or that the primary role allows the given sub role. Please make sure
     * the uri you're building is actually valid under the spec manually.
     */
    public static function buildUri(
        string $roleType,
        string $primaryRole,
        string $subRole = null
    ): string {
        $isMembership = ($roleType == self::ROLE_TYPE_MEMBERSHIP);
        $uri = self::ROLE_PREFIX . $roleType;
        if (!$isMembership) $uri .= '/' . self::ROLE_PERSON;
        if ($subRole) {
            $uri .= '/' . $primaryRole . '#' . $subRole;
        }
        else {
            $uri .= '#' . $primaryRole;
        }
        return $uri;
    }

    /**
     * Initalize the list of roles that are allowed to use the lookup fake user
     * to real user mapping function.
     *
     * I can't think of a good way to do this, so this is just a basic, if
     * they're an instructor in the course, they can have access.
     */
    private static function initStaticRoles()
    {
        if (self::$instructorRoles && self::$institutionAdminRole) return;
        // all admins and instructors are allowed lookup
        self::$instructorRoles = [
            // institution admins
            self::buildUri(self::ROLE_TYPE_MEMBERSHIP, self::ROLE_INSTRUCTOR)
                => 0,
            self::buildUri(self::ROLE_TYPE_MEMBERSHIP, self::ROLE_INSTRUCTOR,
                           self::SUBROLE_LECTURER) => 1,
            self::buildUri(self::ROLE_TYPE_MEMBERSHIP, self::ROLE_INSTRUCTOR,
                           self::SUBROLE_PRIMARY_INSTRUCTOR) => 2,
            self::buildUri(self::ROLE_TYPE_MEMBERSHIP, self::ROLE_INSTRUCTOR,
                           self::SUBROLE_SECONDARY_INSTRUCTOR) => 3
        ];
        self::$institutionAdminRole = self::buildUri(
            self::ROLE_TYPE_INSTITUTION, self::ROLE_ADMINISTRATOR);
    }

}
