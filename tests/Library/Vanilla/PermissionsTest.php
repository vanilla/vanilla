<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Permissions;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for the `Permissions` class.
 */
class PermissionsTest extends SiteTestCase
{
    use ExpectExceptionTrait;

    private const RANKED_PERMISSIONS = [
        "Garden.Admin.Allow", // virtual permission for isAdmin
        "Garden.Settings.Manage",
        "Garden.Community.Manage",
        "Garden.Moderation.Manage",
        "Garden.Curation.Manage",
        "Garden.SignIn.Allow",
    ];

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        \Gdn::permissionModel()->define(["namespace.resource.lowercase" => 0]);
    }

    /**
     *  Test setup.
     */
    public function setUp(): void
    {
        $this->enableCaching();
        parent::setUp();
    }

    public function testAdd()
    {
        $permissions = new Permissions();

        $permissions->add("Vanilla.Discussions.Add", 10);
        $this->assertTrue($permissions->has("Vanilla.Discussions.Add", 10));
    }

    public function testCompileAndLoad()
    {
        $permissions = new Permissions();
        $exampleRows = [
            [
                "PermissionID" => 1,
                "RoleID" => 8,
                "JunctionTable" => null,
                "JunctionColumn" => null,
                "JunctionID" => null,
                "Garden.SignIn.Allow" => 1,
                "Garden.Settings.Manage" => 0,
                "Vanilla.Discussions.View" => 1,
            ],
            [
                "PermissionID" => 2,
                "RoleID" => 8,
                "JunctionTable" => "Category",
                "JunctionColumn" => "PermissionCategoryID",
                "JunctionID" => 10,
                "Vanilla.Discussions.Add" => 1,
            ],
        ];
        $permissions->compileAndLoad($exampleRows);

        $this->assertTrue($permissions->has("Garden.SignIn.Allow"));
        $this->assertTrue($permissions->has("Vanilla.Discussions.View"));
        $this->assertFalse($permissions->has("Garden.Settings.Manage"));
        $this->assertTrue($permissions->has("Vanilla.Discussions.Add", 10));
    }

    /**
     * Test HasAny functionality
     *
     * @param array $permissionsCheck permissions to check
     * @param boolean $expectedResult expected success or fail
     *
     * @dataProvider provideTestHasAnyArray
     */
    public function testHasAny($permissionsCheck, $expectedResult = false)
    {
        $permissions = new Permissions(["Vanilla.Comments.Add"]);
        if ($expectedResult) {
            $this->assertTrue($permissions->hasAny($permissionsCheck));
        } else {
            $this->assertFalse($permissions->hasAny($permissionsCheck));
        }
    }

    /**
     * Provide test data for {@link testHasAny()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestHasAnyArray()
    {
        return [
            "Has Vanilla.Comments.Add and others" => [
                [
                    "Vanilla.Discussions.Add",
                    "Vanilla.Discussions.Edit",
                    "Vanilla.Comments.Add",
                    "Vanilla.Comments.Edit",
                ],
                true,
            ],
            "Has comments.add and others" => [
                ["discussions.add", "discussions.edit", "comments.add", "comments.edit"],
                true,
            ],
            "Doesn't have any full name " => [
                ["Garden.Settings.Manage", "Garden.Community.Manage", "Garden.Moderation.Manage"],
                false,
            ],
            "Doesn't have any new name " => [["site.manage", "community.manage", "moderation.manage"], false],
        ];
    }

    /**
     * Test that our -1 ID is handled.
     */
    public function testGlobalID()
    {
        $permissions = new Permissions();
        $exampleRows = [
            [
                "PermissionID" => 1,
                "RoleID" => 8,
                "JunctionTable" => "Category",
                "JunctionColumn" => "PermissionCategoryID",
                "JunctionID" => -1,
                "Vanilla.Discussions.View" => 1,
            ],
        ];
        $permissions->compileAndLoad($exampleRows);

        $this->assertTrue($permissions->has("Vanilla.Discussions.View"));
        $this->assertTrue($permissions->has("Vanilla.Discussions.View", null, Permissions::CHECK_MODE_GLOBAL_ONLY));
        $this->assertTrue($permissions->has("Vanilla.Discussions.View", -1));
    }

    /**
     * Test our conditional junction logic.
     */
    public function testResourceIfJunction()
    {
        $globalPermPos = ["My.Junction.View" => 1];
        $resourcePermNeg = [
            "JunctionTable" => "MyJunction",
            "JunctionColumn" => "someColumn",
            "JunctionID" => 62,
            "My.Junction.View" => 0,
        ];
        $globalPermNeg = ["My.Junction.View" => 0];
        $resourcePermPos = [
            "JunctionTable" => "MyJunction",
            "JunctionColumn" => "someColumn",
            "JunctionID" => 62,
            "My.Junction.View" => 1,
        ];
        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermPos]);

        $this->assertTrue(
            $permissions->has("My.Junction.View", 62, Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION, "MyJunction")
        );

        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermPos, $resourcePermNeg]);

        $this->assertFalse(
            $permissions->has("My.Junction.View", 62, Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION, "MyJunction")
        );

        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermNeg]);

        $this->assertFalse(
            $permissions->has("My.Junction.View", 62, Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION, "MyJunction")
        );

        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermNeg, $resourcePermPos]);

        $this->assertTrue(
            $permissions->has("My.Junction.View", 62, Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION, "MyJunction")
        );
    }

    /**
     * Test HasAny functionality
     *
     * @param array $permissionsCheck permissions to check
     * @param boolean $expectedResult expected success or fail
     *
     * @dataProvider provideTestHasAllArray
     */
    public function testHasAll($permissionsCheck, $expectedResult)
    {
        $permissions = new Permissions([
            "Vanilla.Discussions.Add",
            "Vanilla.Discussions.Edit",
            "Vanilla.Comments.Add",
            "Vanilla.Comments.Edit",
        ]);
        if ($expectedResult) {
            $this->assertTrue($permissions->hasAll($permissionsCheck));
        } else {
            $this->assertFalse($permissions->hasAll($permissionsCheck));
        }
    }

    /**
     * Provide test data for {@link testHasAll()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestHasAllArray()
    {
        return [
            "Has all Full/Old names" => [
                [
                    "Vanilla.Discussions.Add",
                    "Vanilla.Discussions.Edit",
                    "Vanilla.Comments.Add",
                    "Vanilla.Comments.Edit",
                    "comments.edit",
                ],
                true,
            ],
            "Has all new names" => [["discussions.add", "discussions.edit", "comments.add", "comments.edit"], true],
            "Doesn't have all full name " => [
                [
                    "Vanilla.Discussions.Announce",
                    "Vanilla.Discussions.Add",
                    "Vanilla.Discussions.Edit",
                    "Vanilla.Comments.Add",
                    "Vanilla.Comments.Edit",
                ],
                false,
            ],
            "Doesn't have all new name " => [
                ["discussions.announce", "discussions.add", "discussions.edit", "comments.add", "comments.edit"],
                false,
            ],
        ];
    }

    /**
     * If you don't require any permission then the user should have all/any of them.
     */
    public function testHasAnyAllEmpty()
    {
        $perm = new Permissions();

        $this->assertTrue($perm->hasAll([]));
        $this->assertTrue($perm->hasAny([]));
    }

    /**
     * Test Untranslate API permission name
     *
     * @param string $permission API permission name
     * @param string $expectedResult Old style permission name
     *
     * @dataProvider provideTestUnTranslateArray
     */
    public function testUnTranslatePermission($permission, $expectedResult)
    {
        $permissions = new Permissions();
        $oldName = $permissions->untranslatePermission($permission);

        $this->assertSame(@$expectedResult, $oldName);
    }

    /**
     * Provide test data for {@link testUnTranslatePermission()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestUnTranslateArray()
    {
        return [
            "Permission resource.lowercase" => ["resource.lowercase", "namespace.resource.lowercase"],
            "Permission conversations.moderate" => ["conversations.moderate", "Conversations.Moderation.Manage"],
            "Permission comments.email" => ["comments.email", "Email.Comments.Add"],
            "Permission conversations.email" => ["conversations.email", "Email.Conversations.Add"],
            "Permission discussions.email" => ["discussions.email", "Email.Discussions.Add"],
            "Permission Reactions.Negative.Add" => ["Reactions.Negative.Add", "Reactions.Negative.Add"],
            "Permission Reactions.Positive.Add " => ["Reactions.Positive.Add", "Reactions.Positive.Add"],
            "Permission discussions.edit" => ["discussions.edit", "Vanilla.Discussions.Edit"],
            "Permission comments.edit" => ["comments.edit", "Vanilla.Comments.Edit"],
            "Permission discussions.add" => ["discussions.add", "Vanilla.Discussions.Add"],
        ];
    }

    /**
     * Test Has permission function
     *
     * @param string $permission Permission name
     * @param integer|null $permissionId Permission ID
     * @param string|null $checkMode method of checking
     * @param boolean $expectedHas should be error or not.
     *
     * @dataProvider provideTestHasArray
     */
    public function testHas($permission, $permissionId = null, $checkMode = null, $expectedHas = false)
    {
        $permissions = new Permissions(["Vanilla.Discussions.View", "Vanilla.Discussions.Add" => [10]]);

        if ($expectedHas) {
            if ($checkMode === null) {
                $this->assertTrue($permissions->has($permission, $permissionId));
            } else {
                $this->assertTrue($permissions->has($permission, $permissionId, $checkMode));
            }
        } else {
            if ($checkMode === null) {
                $this->assertFalse($permissions->has($permission, $permissionId));
            } else {
                $this->assertFalse($permissions->has($permission, $permissionId, $checkMode));
            }
        }
    }

    /**
     * Provide test data for {@link testHas()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestHasArray()
    {
        return [
            "Has Vanilla.Discussions.View permission" => ["Vanilla.Discussions.View", null, null, true],
            "Has Vanilla.Discussions.Add permission" => ["Vanilla.Discussions.Add", null, null, true],
            "Has Vanilla.Discussions.Add 10 permission" => ["Vanilla.Discussions.Add", 10, null, true],
            "Has Vanilla.Discussions.Add 10 Check Mode permission" => [
                "Vanilla.Discussions.Add",
                10,
                Permissions::CHECK_MODE_RESOURCE_ONLY,
                true,
            ],
            "Has discussions.add permission" => ["discussions.add", null, null, true],
            "Has discussions.add 10 permission" => ["discussions.add", 10, null, true],
            "Does not have Garden.Settings.Manage permission" => ["Garden.Settings.Manage", null, null, false],
            "Does not have Vanilla.Discussions.Add 100 permission" => ["Vanilla.Discussions.Add", 100, null, false],
            "Does not have Vanilla.Discussions.Add Global Mode permission" => [
                "Vanilla.Discussions.Add",
                null,
                Permissions::CHECK_MODE_GLOBAL_ONLY,
                false,
            ],
            "Does not have Vanilla.Discussions.Edit permission" => ["Vanilla.Discussions.Edit", null, null, false],
            "Does not have discussions.edit permission" => ["discussions.edit", null, null, false],
            'Does not have "" permission' => ["", null, null, false],
        ];
    }

    public function testMerge()
    {
        $permissions = new Permissions(["Garden.SignIn.Allow", "Vanilla.Discussions.Add" => [10]]);
        $additionalPermissions = new Permissions(["Garden.Profiles.View", "Vanilla.Discussions.Add" => [20, 30]]);
        $permissions->merge($additionalPermissions);

        $this->assertTrue($permissions->has("Garden.SignIn.Allow"));
        $this->assertTrue($permissions->has("Garden.Profiles.View"));
        $this->assertTrue($permissions->has("Vanilla.Discussions.Add", 10));
        $this->assertTrue($permissions->has("Vanilla.Discussions.Add", 20));
        $this->assertTrue($permissions->has("Vanilla.Discussions.Add", 30));
    }

    public function testOverwrite()
    {
        $permissions = new Permissions([
            "Garden.Settings.Manage",
            "Email.Discussions.Add",
            "Garden.Discussions.Add" => [1, 2, 3],
        ]);

        $this->assertTrue($permissions->has("Garden.Settings.Manage"));
        $this->assertTrue($permissions->has("discussions.email"));

        $this->assertTrue($permissions->has("Garden.Discussions.Add", 1));
        $this->assertTrue($permissions->has("Garden.Discussions.Add", 2));
        $this->assertTrue($permissions->has("Garden.Discussions.Add", 3));

        $permissions->overwrite("Garden.Settings.Manage", false);
        $permissions->overwrite("Garden.Discussions.Add", [4, 5, 6]);
        $this->assertFalse($permissions->has("Garden.Settings.Manage"));
        $this->assertFalse($permissions->has("Garden.Discussions.Add", 1));
        $this->assertFalse($permissions->has("Garden.Discussions.Add", 2));
        $this->assertFalse($permissions->has("Garden.Discussions.Add", 3));
        $this->assertTrue($permissions->has("Garden.Discussions.Add", 4));
        $this->assertTrue($permissions->has("Garden.Discussions.Add", 5));
        $this->assertTrue($permissions->has("Garden.Discussions.Add", 6));
    }

    public function testRemove()
    {
        $permissions = new Permissions([
            "Vanilla.Discussions.Add" => [10],
            "Vanilla.Discussions.Edit" => [10],
        ]);

        $permissions->remove("Vanilla.Discussions.Edit", 10);

        $this->assertTrue($permissions->has("Vanilla.Discussions.Add", 10));
        $this->assertFalse($permissions->has("Vanilla.Discussions.Edit", 10));
    }

    public function testSet()
    {
        $permissions = new Permissions();
        $permissions->set("Garden.SignIn.Allow", true);

        $this->assertTrue($permissions->has("Garden.SignIn.Allow"));
    }

    public function testSetPermissions()
    {
        $permissions = new Permissions();
        $permissions->setPermissions([
            "Garden.SignIn.Allow",
            "Vanilla.Discussions.Add",
            "Vanilla.Discussions.Edit",
            "Vanilla.Comments.Add" => [10],
            "Vanilla.Comments.Edit" => [10],
        ]);

        $this->assertTrue($permissions->has("Garden.SignIn.Allow"));
        $this->assertTrue($permissions->has("Vanilla.Discussions.Add"));
        $this->assertTrue($permissions->has("Vanilla.Discussions.Edit"));
        $this->assertTrue($permissions->has("Vanilla.Comments.Add", 10));
        $this->assertTrue($permissions->has("Vanilla.Comments.Edit", 10));
        $this->assertFalse($permissions->has("Garden.Settings.Manage"));
    }

    /**
     * Test permissions with the any.
     */
    public function testAnyIDPermission()
    {
        $perms = new Permissions();

        $this->assertFalse($perms->has("foo"));

        $perms->set("foo", true);
        $this->assertTrue($perms->has("foo"));

        $perms->add("bar", [1, 2]);
        $this->assertFalse($perms->has("bar", 3));
        $this->assertTrue($perms->has("bar"));
    }

    /**
     * Make sure that removing all permissions will return false for an any permission scenario.
     */
    public function testAddRemovePermission()
    {
        $perms = new Permissions();
        $perms->add("foo", 1);
        $perms->remove("foo", 1);
        $this->assertFalse($perms->has("foo"));
    }

    /**
     * An admin should have all permissions.
     */
    public function testAdmin()
    {
        $perm = new Permissions();
        $perm->setAdmin(true);

        $this->assertTrue($perm->has("foo"));
        $this->assertTrue($perm->hasAll(["foo", "bar", "baz"]));
        $this->assertTrue($perm->hasAny(["foo", "bar", "baz"]));

        return $perm;
    }

    /**
     * A ban should override an existing permission.
     */
    public function testBan()
    {
        $perm = $this->createBanned();

        $this->assertFalse($perm->has("foo"));

        return $perm;
    }

    /**
     * Removing an existing ban should restore an existing permission.
     *
     * @param Permissions $perm A permissions array with a ban.
     * @depends testBan
     */
    public function testRemoveBan(Permissions $perm)
    {
        $perm->removeBan(Permissions::BAN_BANNED);

        $this->assertTrue($perm->has("foo"));
    }

    /**
     * A ban can be overridden by passing it as a permission name in a check.
     *
     * @depends testBan
     */
    public function testBanOverrides()
    {
        $perm = $this->createBanned();

        $this->assertTrue($perm->has(["foo", Permissions::BAN_BANNED]));
        $this->assertTrue($perm->hasAll(["foo", Permissions::BAN_BANNED]));
        $this->assertTrue($perm->hasAny(["foo", Permissions::BAN_BANNED]));
    }

    /**
     * A {@link Permissions::hasAny()} test should return **false** if you still don't have any permissions even though you are overriding bans.
     */
    public function testBanOverrideWithNone()
    {
        $perm = $this->createBanned();

        $this->assertTrue($perm->hasAny([Permissions::BAN_BANNED]));
        $this->assertTrue($perm->hasAll([Permissions::BAN_BANNED]));

        $this->assertFalse($perm->hasAny(["bink", Permissions::BAN_BANNED]));
    }

    /**
     * Admin should not override bans.
     *
     * @param Permissions $perm A permissions array with admin.
     * @depends testAdmin
     */
    public function testBanAdmin(Permissions $perm)
    {
        $perm->addBan(Permissions::BAN_BANNED);

        $this->assertFalse($perm->has("foo"));
        $this->assertFalse($perm->hasAll(["foo", "bar", "baz"]));
        $this->assertFalse($perm->hasAny(["foo", "bar", "baz"]));
    }

    /**
     * Bans can specify permissions that are exceptions.
     */
    public function testBanException()
    {
        $perm = $this->createBanned();
        $perm->addBan(Permissions::BAN_BANNED, ["except" => ["foo", "baz"]]);

        $this->assertTrue($perm->has("foo"));
    }

    /**
     * Admin status can be specified as a ban exception with the "admin" permission string.
     */
    public function testBanAdminException()
    {
        $perm = new Permissions();
        $perm->setAdmin(true)->addBan(Permissions::BAN_BANNED, ["except" => "admin"]);

        $this->assertTrue($perm->has("any"));
    }

    /**
     * Test {@link Permissions::getBan()}.
     */
    public function testGetBan()
    {
        $perm = $this->createBanned();

        $ban = $perm->getBan();

        $this->assertEquals(Permissions::BAN_BANNED, $ban["type"]);
        $this->assertEquals(401, $ban["code"]);
        $this->assertArrayHasKey("msg", $ban);
    }

    /**
     * You should get the next ban if you bypass the first one.
     *
     * @depends testGetBan
     */
    public function testBanBypass()
    {
        $perm = $this->createBanned();
        $perm->addBan(Permissions::BAN_DELETED);

        $ban = $perm->getBan([Permissions::BAN_BANNED]);
        $this->assertEquals(Permissions::BAN_DELETED, $ban["type"]);
    }

    /**
     * You should get a prepended ban before other ones.
     *
     * @depends testGetBan
     */
    public function testPrependBan()
    {
        $perm = $this->createBanned();

        $perm->addBan(Permissions::BAN_DELETED, [], true);

        $ban = $perm->getBan();
        $this->assertEquals(Permissions::BAN_DELETED, $ban["type"]);
    }

    /**
     * Test a basic JSON serialize array.
     */
    public function testJsonSerialize()
    {
        $permissions = new Permissions();

        $permissions->setPermissions([
            "Vanilla.Discussions.Add",
            "Vanilla.Discussions.Edit",
            "Vanilla.Comments.Add" => [10],
            "Vanilla.Comments.Edit" => [10],
        ]);

        $json = $permissions->jsonSerialize();
        $this->assertEquals(
            [
                "discussions.add" => true,
                "discussions.edit" => true,
                "comments.add" => [10],
                "comments.edit" => [10],
            ],
            $json["permissions"]
        );
    }

    /**
     * Test a per-category permission, but with a global override to true.
     */
    public function testJsonSerializeGlobalOverride()
    {
        $permissions = new Permissions();

        $permissions->set("Vanilla.Discussions.Add", true);
        $permissions->set("Vanilla.Discussions.Edit", false);
        $permissions->add("Vanilla.Discussions.Add", [10]);
        $permissions->add("Vanilla.Discussions.Edit", [10]);

        $json = $permissions->jsonSerialize();
        $this->assertEquals(
            [
                "discussions.add" => true,
                "discussions.edit" => [10],
            ],
            $json["permissions"]
        );
    }

    /**
     * Ban names must start with "!".
     */
    public function testInvalidBanName()
    {
        $this->expectException(\InvalidArgumentException::class);

        $perm = new Permissions();
        $perm->addBan("foo");
    }

    /**
     * Create a permissions object with a permission and a ban.
     *
     * @return Permissions Returns a new {@link Permissions} object.
     */
    private function createBanned()
    {
        $perm = new Permissions();
        $perm->set("foo", true)->addBan(Permissions::BAN_BANNED);

        return $perm;
    }

    /**
     * Admins should have the virtual ranking permission
     */
    public function testAdminRank()
    {
        $perms = new Permissions();
        $perms->setAdmin(true);
        $this->assertSame("Garden.Admin.Allow", $perms->getRankingPermission());
    }

    /**
     * Admins should be higher than settings manage.
     */
    public function testAdminHigherThanSettings()
    {
        $admin = new Permissions();
        $admin->setAdmin(true);
        $settings = new Permissions();
        $settings->set("Garden.Settings.Manage", true);
        $this->assertSame(1, $admin->compareRankTo($settings));
        $this->assertSame(-1, $settings->compareRankTo($admin));
    }

    /**
     * The settings manage should have all other ranked permissions.
     */
    public function testHasRanked()
    {
        $settings = new Permissions();
        $settings->set("Garden.Settings.Manage", true);

        foreach (self::RANKED_PERMISSIONS as $i => $perm) {
            if ($i === 0) {
                $this->assertFalse($settings->hasRanked($perm));
            } else {
                $this->assertTrue($settings->hasRanked($perm));
            }
        }
    }

    /**
     * Non-ranking permissions should behave gracefully.
     */
    public function testNonRankingPermissionCheck()
    {
        $perms = new Permissions();
        $perms->set("foo", true);

        $this->assertTrue($perms->hasRanked("foo"));
        $this->assertSame("", $perms->getRankingPermission());
        $this->assertFalse($perms->hasRanked("Garden.SignIn.Allow"));
    }

    /**
     * Verify an admin user does not automatically get the system permission.
     */
    public function testNoAdminSystem(): void
    {
        $perms = new Permissions();
        $perms->setAdmin(true);
        $this->assertFalse($perms->has(Permissions::PERMISSION_SYSTEM));
    }

    /**
     * Test our API output of permissions.
     */
    public function testApiOutput()
    {
        $globalPermPos = ["Vanilla.Discussions.View" => 1];
        $resourcePermNeg = [
            "JunctionTable" => "Category",
            "JunctionColumn" => "PermissionCategoryID",
            "JunctionID" => 62,
            "Vanilla.Discussions.View" => 0,
        ];
        $resourcePermPos = [
            "JunctionTable" => "Category",
            "JunctionColumn" => "PermissionCategoryID",
            "JunctionID" => 50,
            "Vanilla.Discussions.View" => 1,
        ];
        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermPos, $resourcePermNeg, $resourcePermPos]);
        $permissions->addJunctionAliases([
            "Category" => [
                24 => 62,
            ],
        ]);
        $permissions->addJunctions([
            "Category" => [100, 101],
        ]);

        $actual = $permissions->asApiOutput(true);
        $expected = [
            "permissions" => [
                [
                    "type" => "global",
                    "id" => null,
                    "permissions" => [
                        "discussions.view" => true,
                    ],
                ],
                [
                    "type" => "category",
                    "id" => 50,
                    "permissions" => [
                        "discussions.view" => true,
                    ],
                ],
            ],
            "isAdmin" => false,
            "isSysAdmin" => false,
            "junctions" => [
                "category" => [62, 50, 100, 101],
            ],
            "junctionAliases" => [
                "category" => [
                    24 => 62,
                ],
            ],
        ];
        $this->assertSame($expected, $actual);

        // Empty
        $permissions = new Permissions();
        $this->assertEquals(
            [
                "permissions" => [
                    [
                        "type" => "global",
                        "id" => null,
                        "permissions" => [],
                    ],
                ],
                "isAdmin" => false,
                "isSysAdmin" => false,
            ],
            $permissions->asApiOutput(false)
        );
        $this->assertEquals(
            [
                "permissions" => [
                    [
                        "type" => "global",
                        "id" => null,
                        "permissions" => [],
                    ],
                ],
                "isAdmin" => false,
                "isSysAdmin" => false,
                "junctions" => new \stdClass(),
                "junctionAliases" => new \stdClass(),
            ],
            $permissions->asApiOutput(true)
        );
    }

    /**
     * Test validation of permission names.
     */
    public function testNameValidation()
    {
        $permissions = new Permissions();
        $permissions->setValidPermissionNames(["perm1", "perm2"]);

        // This is valid
        $permissions->has("perm2");

        // This isn't.
        $this->runWithExpectedExceptionMessage("Invalid permission name: 'perm3'", function () use ($permissions) {
            $permissions->has("perm3");
        });
    }
}
