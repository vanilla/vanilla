<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ExtendedUserFieldsExpander;
use Gdn;
use Symfony\Contracts\Cache\CacheTrait;
use UserProfileFieldsExpander;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Web\APIExpandMiddleware;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for the "profileFields" and "extended" expand user options.
 */
class UserProfileFieldsExpandTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait, CacheTrait;

    /**
     * We need to explicitly register these expanders because our test harness doesn't allow us to set a feature flag
     * before loading the addons. In production, whether these get registered will depend on the CustomProfileFields
     * feature flag.
     */
    public function setUp(): void
    {
        self::enableFeature(ProfileFieldModel::FEATURE_FLAG);
        parent::setUp();
    }

    /**
     * Test the extended and profileField expand parameters.
     */
    public function testProfileFieldExpand(): void
    {
        $record = [
            "apiName" => "ProfileFieldExpand",
            "label" => "profile field expand",
            "description" => "test expanding",
            "dataType" => "text",
            "formType" => "text",
            "visibility" => "public",
            "mutability" => "all",
            "displayOptions" => ["userCards" => true, "posts" => true],
            "registrationOptions" => ProfileFieldModel::REGISTRATION_HIDDEN,
        ];

        $this->api()->post("/profile-fields", $record);

        $user = $this->createUser();
        $this->api()->patch("/users/{$user["userID"]}/profile-fields", ["ProfileFieldExpand" => "foo"]);

        $expandedUserRecord = $this->api()
            ->get("/users/{$user["userID"]}?expand=profileFields")
            ->getBody();

        // The profileField field and data should be part of the record.
        $this->assertArrayHasKey("profileFields", $expandedUserRecord);
        $this->assertSame("foo", $expandedUserRecord["profileFields"]["ProfileFieldExpand"]);

        // We should also be able to get the same data using the "extended" expand parameter.
        $expandedUserRecord = $this->api()
            ->get("/users/{$user["userID"]}?expand=extended")
            ->getBody();

        $this->assertArrayHasKey("extended", $expandedUserRecord);
        $this->assertSame("foo", $expandedUserRecord["extended"]["ProfileFieldExpand"]);

        // Also test that the user index endpoint also returns profile fields.
        // This specifically tests that the profile field name is not converted to camelCase like other user properties.
        $expandedUsersRecords = $this->api()
            ->get("/users?userID={$user["userID"]}&expand=profileFields")
            ->getBody();
        $this->assertCount(1, $expandedUsersRecords);
        $this->assertArrayHasKey("ProfileFieldExpand", $expandedUsersRecords[0]["profileFields"]);
        $this->assertSame("foo", $expandedUsersRecords[0]["profileFields"]["ProfileFieldExpand"]);
    }
}
