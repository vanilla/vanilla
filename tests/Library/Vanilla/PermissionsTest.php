<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use VanillaTests\BootstrapTestCase;
use Vanilla\Permissions;

/**
 * Tests for the `Permissions` class.
 */
class PermissionsTest extends BootstrapTestCase {
    private const RANKED_PERMISSIONS = [
        'Garden.Admin.Allow', // virtual permission for isAdmin
        'Garden.Settings.Manage',
        'Garden.Community.Manage',
        'Garden.Moderation.Manage',
        'Garden.Curation.Manage',
        'Garden.SignIn.Allow',
    ];

    public function testAdd() {
        $permissions = new Permissions();

        $permissions->add('Vanilla.Discussions.Add', 10);
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
    }

    public function testCompileAndLoad() {
        $permissions = new Permissions();
        $exampleRows = [
            [
                'PermissionID' => 1,
                'RoleID' => 8,
                'JunctionTable' => null,
                'JunctionColumn' => null,
                'JunctionID' => null,
                'Garden.SignIn.Allow' => 1,
                'Garden.Settings.Manage' => 0,
                'Vanilla.Discussions.View' => 1,
            ],
            [
                'PermissionID' => 2,
                'RoleID' => 8,
                'JunctionTable' => 'Category',
                'JunctionColumn' => 'PermissionCategoryID',
                'JunctionID' => 10,
                'Vanilla.Discussions.Add' => 1,
            ],
        ];
        $permissions->compileAndLoad($exampleRows);

        $this->assertTrue($permissions->has('Garden.SignIn.Allow'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.View'));
        $this->assertFalse($permissions->has('Garden.Settings.Manage'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
    }

    public function testHasAny() {
        $permissions = new Permissions([
            'Vanilla.Comments.Add',
        ]);

        $this->assertTrue($permissions->hasAny([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit',
        ]));
        $this->assertFalse($permissions->hasAny([
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage',
        ]));
    }

    /**
     * Test that our -1 ID is handled.
     */
    public function testGlobalID() {
        $permissions = new Permissions();
        $exampleRows = [
            [
                'PermissionID' => 1,
                'RoleID' => 8,
                'JunctionTable' => 'Category',
                'JunctionColumn' => 'PermissionCategoryID',
                'JunctionID' => -1,
                'Vanilla.Discussions.View' => 1,
            ],
        ];
        $permissions->compileAndLoad($exampleRows);

        $this->assertTrue($permissions->has('Vanilla.Discussions.View'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.View', null, Permissions::CHECK_MODE_GLOBAL_ONLY));
        $this->assertTrue($permissions->has('Vanilla.Discussions.View', -1));
    }

    /**
     * Test our conditional junction logic.
     */
    public function testResourceIfJunction() {
        $globalPermPos = ['My.Junction.View' => 1];
        $resourcePermNeg = [
            'JunctionTable' => 'MyJunction',
            'JunctionColumn' => 'someColumn',
            'JunctionID' => 62,
            'My.Junction.View' => 0,
        ];
        $globalPermNeg = ['My.Junction.View' => 0];
        $resourcePermPos = [
            'JunctionTable' => 'MyJunction',
            'JunctionColumn' => 'someColumn',
            'JunctionID' => 62,
            'My.Junction.View' => 1,
        ];
        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermPos]);

        $this->assertTrue($permissions->has(
            'My.Junction.View',
            62,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            'MyJunction'
        ));

        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermPos, $resourcePermNeg]);

        $this->assertFalse($permissions->has(
            'My.Junction.View',
            62,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            'MyJunction'
        ));

        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermNeg]);

        $this->assertFalse($permissions->has(
            'My.Junction.View',
            62,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            'MyJunction'
        ));

        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermNeg, $resourcePermPos]);

        $this->assertTrue($permissions->has(
            'My.Junction.View',
            62,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            'MyJunction'
        ));
    }

    public function testHasAll() {
        $permissions = new Permissions([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit',
        ]);

        $this->assertTrue($permissions->hasAll([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit',
        ]));
        $this->assertFalse($permissions->hasAll([
            'Vanilla.Discussions.Announce',
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit',
        ]));
    }

    /**
     * If you don't require any permission then the user should have all/any of them.
     */
    public function testHasAnyAllEmpty() {
        $perm = new Permissions();

        $this->assertTrue($perm->hasAll([]));
        $this->assertTrue($perm->hasAny([]));
    }

    public function testHas() {
        $permissions = new Permissions([
            'Vanilla.Discussions.View',
            'Vanilla.Discussions.Add' => [10],
        ]);

        $this->assertTrue($permissions->has('Vanilla.Discussions.View'));
        $this->assertFalse($permissions->has('Garden.Settings.Manage'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add'));

        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10, Permissions::CHECK_MODE_RESOURCE_ONLY));
        $this->assertFalse($permissions->has('Vanilla.Discussions.Add', 100));

        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', null));
        $this->assertTrue($permissions->has('Vanilla.Discussions.View', null));
        $this->assertFalse($permissions->has('Vanilla.Discussions.Add', null, Permissions::CHECK_MODE_GLOBAL_ONLY));
        $this->assertFalse($permissions->has('Vanilla.Discussions.Edit'));

        $this->assertFalse($permissions->has(""));
    }

    public function testMerge() {
        $permissions = new Permissions([
            'Garden.SignIn.Allow',
            'Vanilla.Discussions.Add' => [10],
        ]);
        $additionalPermissions = new Permissions([
            'Garden.Profiles.View',
            'Vanilla.Discussions.Add' => [20, 30],
        ]);
        $permissions->merge($additionalPermissions);

        $this->assertTrue($permissions->has('Garden.SignIn.Allow'));
        $this->assertTrue($permissions->has('Garden.Profiles.View'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 20));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 30));
    }

    public function testOverwrite() {
        $permissions = new Permissions([
            'Garden.Settings.Manage',
            'Garden.Discussions.Add' => [1, 2, 3],
        ]);

        $this->assertTrue($permissions->has('Garden.Settings.Manage'));
        $this->assertTrue($permissions->has('Garden.Discussions.Add', 1));
        $this->assertTrue($permissions->has('Garden.Discussions.Add', 2));
        $this->assertTrue($permissions->has('Garden.Discussions.Add', 3));

        $permissions->overwrite('Garden.Settings.Manage', false);
        $permissions->overwrite('Garden.Discussions.Add', [4, 5, 6]);
        $this->assertFalse($permissions->has('Garden.Settings.Manage'));
        $this->assertFalse($permissions->has('Garden.Discussions.Add', 1));
        $this->assertFalse($permissions->has('Garden.Discussions.Add', 2));
        $this->assertFalse($permissions->has('Garden.Discussions.Add', 3));
        $this->assertTrue($permissions->has('Garden.Discussions.Add', 4));
        $this->assertTrue($permissions->has('Garden.Discussions.Add', 5));
        $this->assertTrue($permissions->has('Garden.Discussions.Add', 6));
    }

    public function testRemove() {
        $permissions = new Permissions([
            'Vanilla.Discussions.Add' => [10],
            'Vanilla.Discussions.Edit' => [10],
        ]);

        $permissions->remove('Vanilla.Discussions.Edit', 10);

        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
        $this->assertFalse($permissions->has('Vanilla.Discussions.Edit', 10));
    }

    public function testSet() {
        $permissions = new Permissions();
        $permissions->set('Garden.SignIn.Allow', true);

        $this->assertTrue($permissions->has('Garden.SignIn.Allow'));
    }

    public function testSetPermissions() {
        $permissions = new Permissions();
        $permissions->setPermissions([
            'Garden.SignIn.Allow',
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add' => [10],
            'Vanilla.Comments.Edit' => [10],
        ]);

        $this->assertTrue($permissions->has('Garden.SignIn.Allow'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Edit'));
        $this->assertTrue($permissions->has('Vanilla.Comments.Add', 10));
        $this->assertTrue($permissions->has('Vanilla.Comments.Edit', 10));
        $this->assertFalse($permissions->has('Garden.Settings.Manage'));
    }

    /**
     * Test permissions with the any.
     */
    public function testAnyIDPermission() {
        $perms = new Permissions();

        $this->assertFalse($perms->has('foo'));

        $perms->set('foo', true);
        $this->assertTrue($perms->has('foo'));

        $perms->add('bar', [1, 2]);
        $this->assertFalse($perms->has('bar', 3));
        $this->assertTrue($perms->has('bar'));
    }

    /**
     * Make sure that removing all permissions will return false for an any permission scenario.
     */
    public function testAddRemovePermission() {
        $perms = new Permissions();
        $perms->add('foo', 1);
        $perms->remove('foo', 1);
        $this->assertFalse($perms->has('foo'));
    }

    /**
     * An admin should have all permissions.
     */
    public function testAdmin() {
        $perm = new Permissions();
        $perm->setAdmin(true);

        $this->assertTrue($perm->has('foo'));
        $this->assertTrue($perm->hasAll(['foo', 'bar', 'baz']));
        $this->assertTrue($perm->hasAny(['foo', 'bar', 'baz']));

        return $perm;
    }

    /**
     * A ban should override an existing permission.
     */
    public function testBan() {
        $perm = $this->createBanned();

        $this->assertFalse($perm->has('foo'));

        return $perm;
    }

    /**
     * Removing an existing ban should restore an existing permission.
     *
     * @param Permissions $perm A permissions array with a ban.
     * @depends testBan
     */
    public function testRemoveBan(Permissions $perm) {
        $perm->removeBan(Permissions::BAN_BANNED);

        $this->assertTrue($perm->has('foo'));
    }

    /**
     * A ban can be overridden by passing it as a permission name in a check.
     *
     * @depends testBan
     */
    public function testBanOverrides() {
        $perm = $this->createBanned();

        $this->assertTrue($perm->has(['foo', Permissions::BAN_BANNED]));
        $this->assertTrue($perm->hasAll(['foo', Permissions::BAN_BANNED]));
        $this->assertTrue($perm->hasAny(['foo', Permissions::BAN_BANNED]));
    }

    /**
     * A {@link Permissions::hasAny()} test should return **false** if you still don't have any permissions even though you are overriding bans.
     */
    public function testBanOverrideWithNone() {
        $perm = $this->createBanned();

        $this->assertTrue($perm->hasAny([Permissions::BAN_BANNED]));
        $this->assertTrue($perm->hasAll([Permissions::BAN_BANNED]));

        $this->assertFalse($perm->hasAny(['bink', Permissions::BAN_BANNED]));
    }

    /**
     * Admin should not override bans.
     *
     * @param Permissions $perm A permissions array with admin.
     * @depends testAdmin
     */
    public function testBanAdmin(Permissions $perm) {
        $perm->addBan(Permissions::BAN_BANNED);

        $this->assertFalse($perm->has('foo'));
        $this->assertFalse($perm->hasAll(['foo', 'bar', 'baz']));
        $this->assertFalse($perm->hasAny(['foo', 'bar', 'baz']));
    }

    /**
     * Bans can specify permissions that are exceptions.
     */
    public function testBanException() {
        $perm = $this->createBanned();
        $perm->addBan(Permissions::BAN_BANNED, ['except' => ['foo', 'baz']]);

        $this->assertTrue($perm->has('foo'));
    }

    /**
     * Admin status can be specified as a ban exception with the "admin" permission string.
     */
    public function testBanAdminException() {
        $perm = new Permissions();
        $perm->setAdmin(true)
            ->addBan(Permissions::BAN_BANNED, ['except' => 'admin'])
        ;

        $this->assertTrue($perm->has('any'));
    }

    /**
     * Test {@link Permissions::getBan()}.
     */
    public function testGetBan() {
        $perm = $this->createBanned();

        $ban = $perm->getBan();

        $this->assertEquals(Permissions::BAN_BANNED, $ban['type']);
        $this->assertEquals(401, $ban['code']);
        $this->assertArrayHasKey('msg', $ban);
    }

    /**
     * You should get the next ban if you bypass the first one.
     *
     * @depends testGetBan
     */
    public function testBanBypass() {
        $perm = $this->createBanned();
        $perm->addBan(Permissions::BAN_DELETED);

        $ban = $perm->getBan([Permissions::BAN_BANNED]);
        $this->assertEquals(Permissions::BAN_DELETED, $ban['type']);
    }

    /**
     * You should get a prepended ban before other ones.
     *
     * @depends testGetBan
     */
    public function testPrependBan() {
        $perm = $this->createBanned();

        $perm->addBan(Permissions::BAN_DELETED, [], true);

        $ban = $perm->getBan();
        $this->assertEquals(Permissions::BAN_DELETED, $ban['type']);
    }

    /**
     * Test a basic JSON serialize array.
     */
    public function testJsonSerialize() {
        $permissions = new Permissions();

        $permissions->setPermissions([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add' => [10],
            'Vanilla.Comments.Edit' => [10],
        ]);

        $json = $permissions->jsonSerialize();
        $this->assertEquals([
            'discussions.add' => true,
            'discussions.edit' => true,
            'comments.add' => [10],
            'comments.edit' => [10],
        ], $json['permissions']);
    }

    /**
     * Test a per-category permission, but with a global override to true.
     */
    public function testJsonSerializeGlobalOverride() {
        $permissions = new Permissions();

        $permissions->set('Vanilla.Discussions.Add', true);
        $permissions->set('Vanilla.Discussions.Edit', false);
        $permissions->add('Vanilla.Discussions.Add', [10]);
        $permissions->add('Vanilla.Discussions.Edit', [10]);

        $json = $permissions->jsonSerialize();
        $this->assertEquals([
            'discussions.add' => true,
            'discussions.edit' => [10],
        ], $json['permissions']);
    }

    /**
     * Ban names must start with "!".
     */
    public function testInvalidBanName() {
        $this->expectException(\InvalidArgumentException::class);

        $perm = new Permissions();
        $perm->addBan('foo');
    }

    /**
     * Create a permissions object with a permission and a ban.
     *
     * @return Permissions Returns a new {@link Permissions} object.
     */
    private function createBanned() {
        $perm = new Permissions();
        $perm->set('foo', true)
            ->addBan(Permissions::BAN_BANNED)
        ;

        return $perm;
    }

    /**
     * Admins should have the virtual ranking permission
     */
    public function testAdminRank() {
        $perms = new Permissions();
        $perms->setAdmin(true);
        $this->assertSame('Garden.Admin.Allow', $perms->getRankingPermission());
    }

    /**
     * Admins should be higher than settings manage.
     */
    public function testAdminHigherThanSettings() {
        $admin = new Permissions();
        $admin->setAdmin(true);
        $settings = new Permissions();
        $settings->set('Garden.Settings.Manage', true);
        $this->assertSame(1, $admin->compareRankTo($settings));
        $this->assertSame(-1, $settings->compareRankTo($admin));
    }

    /**
     * The settings manage should have all other ranked permissions.
     */
    public function testHasRanked() {
        $settings = new Permissions();
        $settings->set('Garden.Settings.Manage', true);

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
    public function testNonRankingPermissionCheck() {
        $perms = new Permissions();
        $perms->set('foo', true);

        $this->assertTrue($perms->hasRanked('foo'));
        $this->assertSame('', $perms->getRankingPermission());
        $this->assertFalse($perms->hasRanked('Garden.SignIn.Allow'));
    }

    /**
     * Verify an admin user does not automatically get the system permission.
     */
    public function testNoAdminSystem(): void {
        $perms = new Permissions();
        $perms->setAdmin(true);
        $this->assertFalse($perms->has(Permissions::PERMISSION_SYSTEM));
    }

    /**
     * Test our API output of permissions.
     */
    public function testApiOutput() {
        $globalPermPos = ['Vanilla.Discussions.View' => 1];
        $resourcePermNeg = [
            'JunctionTable' => 'Category',
            'JunctionColumn' => 'PermissionCategoryID',
            'JunctionID' => 62,
            'Vanilla.Discussions.View' => 0,
        ];
        $resourcePermPos = [
            'JunctionTable' => 'Category',
            'JunctionColumn' => 'PermissionCategoryID',
            'JunctionID' => 50,
            'Vanilla.Discussions.View' => 1,
        ];
        $permissions = new Permissions();
        $permissions->compileAndLoad([$globalPermPos, $resourcePermNeg, $resourcePermPos]);
        $permissions->addJunctionAliases([
            'Category' => [
                24 => 62,
            ],
        ]);
        $permissions->addJunctions([
            'Category' => [100, 101],
        ]);

        $actual = $permissions->asApiOutput(true);
        $expected = [
            'permissions' => [
                [
                    'type' => 'global',
                    'id' => null,
                    'permissions' => [
                        'discussions.view' => true,
                    ],
                ],
                [
                    'type' => 'category',
                    'id' => 50,
                    'permissions' => [
                        'discussions.view' => true,
                    ],
                ]
            ],
            'isAdmin' => false,
            'junctions' => [
                'category' => [62, 50, 100, 101],
            ],
            'junctionAliases' => [
                'category' => [
                    24 => 62,
                ],
            ],
        ];
        $this->assertSame($expected, $actual);

        // Empty
        $permissions = new Permissions();
        $this->assertEquals([
            'permissions' => [
                [
                    'type' => 'global',
                    'id' => null,
                    'permissions' => [],
                ],
            ],
            'isAdmin' => false,
        ], $permissions->asApiOutput(false));
        $this->assertEquals([
            'permissions' => [
                [
                    'type' => 'global',
                    'id' => null,
                    'permissions' => [],
                ],
            ],
            'isAdmin' => false,
            'junctions' => new \stdClass(),
            'junctionAliases' => new \stdClass(),
        ], $permissions->asApiOutput(true));
    }
}
