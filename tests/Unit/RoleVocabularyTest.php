<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use UBC\LTI\Specs\RoleVocabulary;

class RoleVocabularyTest extends TestCase
{

    public function testEmptyRolesCannotLookupRealUsers()
    {
        $roleVocab = new RoleVocabulary();
        $roles = [];
        $this->assertFalse($roleVocab->canLookupRealUsers($roles));
    }

    public function testLookupRealUsersAllowAdmins()
    {
        $roleVocab = new RoleVocabulary();
        $roles = [
            'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator'
        ];
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
    }

    public function testLookupRealUsersAdminRoleOverrideMembershipRoles()
    {
        $roleVocab = new RoleVocabulary();
        $roles = [
            'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
            'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator'
        ];
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
    }

    public function testLookupRealUsersAllowInstructors()
    {
        $roleVocab = new RoleVocabulary();
        $roles = [
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'
        ];
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
        $rolesWithSubrole = $roles;
        $rolesWithSubrole[] =
            'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer';
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
        $rolesWithSubrole = $roles;
        $rolesWithSubrole[] =
            'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#PrimaryInstructor';
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
        $rolesWithSubrole = $roles;
        $rolesWithSubrole[] =
            'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#SecondaryInstructor';
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
    }

    public function testLookupRealUsersIgnoreSystemAndInstutitionRoles()
    {
        $roleVocab = new RoleVocabulary();
        $roles = [
            'http://purl.imsglobal.org/vocab/lis/v2/institution#Instructor',
            'http://purl.imsglobal.org/vocab/lis/v2/system#Instructor'
        ];
        $this->assertFalse($roleVocab->canLookupRealUsers($roles));
        $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
        $this->assertTrue($roleVocab->canLookupRealUsers($roles));
    }

    public function testTeachingAssistantCannotLookupRealUsers()
    {
        $roleVocab = new RoleVocabulary();
        $roles = [
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
            'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant'
        ];
        $this->assertFalse($roleVocab->canLookupRealUsers($roles));
    }

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBuildUri()
    {
        $expectedUri = '';
        $this->assertEquals(
            'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
            RoleVocabulary::buildUri(
                RoleVocabulary::ROLE_TYPE_SYSTEM,
                RoleVocabulary::ROLE_ADMINISTRATOR
            )
        );
        $this->assertEquals(
            'http://purl.imsglobal.org/vocab/lis/v2/institution/person#Administrator',
            RoleVocabulary::buildUri(
                RoleVocabulary::ROLE_TYPE_INSTITUTION,
                RoleVocabulary::ROLE_ADMINISTRATOR
            )
        );
        $this->assertEquals(
            'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
            RoleVocabulary::buildUri(
                RoleVocabulary::ROLE_TYPE_MEMBERSHIP,
                RoleVocabulary::ROLE_ADMINISTRATOR
            )
        );
        $this->assertEquals(
            'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#Lecturer',
            RoleVocabulary::buildUri(
                RoleVocabulary::ROLE_TYPE_MEMBERSHIP,
                RoleVocabulary::ROLE_INSTRUCTOR,
                RoleVocabulary::SUBROLE_LECTURER
            )
        );
    }
}
