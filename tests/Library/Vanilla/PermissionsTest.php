<?php
/**
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Permissions;

class PermissionsTest extends \PHPUnit_Framework_TestCase {

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
                'Vanilla.Discussions.View' => 1
            ],
            [
                'PermissionID' => 2,
                'RoleID' => 8,
                'JunctionTable' => 'Category',
                'JunctionColumn' => 'PermissionCategoryID',
                'JunctionID' => 10,
                'Vanilla.Discussions.Add' => 1
            ]
        ];
        $permissions->compileAndLoad($exampleRows);

        $this->assertTrue($permissions->has('Garden.SignIn.Allow'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.View'));
        $this->assertFalse($permissions->has('Garden.Settings.Manage'));
        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
    }

    public function testHasAny() {
        $permissions = new Permissions([
            'Vanilla.Comments.Add'
        ]);

        $this->assertTrue($permissions->hasAny([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit'
        ]));
        $this->assertFalse($permissions->hasAny([
            'Garden.Settings.Manage',
            'Garden.Community.Manage',
            'Garden.Moderation.Manage'
        ]));
    }

    public function testHasAll() {
        $permissions = new Permissions([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit'
        ]);

        $this->assertTrue($permissions->hasAll([
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit'
        ]));
        $this->assertFalse($permissions->hasAll([
            'Vanilla.Discussions.Announce',
            'Vanilla.Discussions.Add',
            'Vanilla.Discussions.Edit',
            'Vanilla.Comments.Add',
            'Vanilla.Comments.Edit',
        ]));
    }

    public function testHas() {
        $permissions = new Permissions([
            'Vanilla.Discussions.View',
            'Vanilla.Discussions.Add' => [10]
        ]);

        $this->assertTrue($permissions->has('Vanilla.Discussions.View'));
        $this->assertFalse($permissions->has('Garden.Settings.Manage'));

        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', 10));
        $this->assertFalse($permissions->has('Vanilla.Discussions.Add', 100));

        $this->assertTrue($permissions->has('Vanilla.Discussions.Add', null));
        $this->assertTrue($permissions->has('Vanilla.Discussions.View', null));
        $this->assertFalse($permissions->has('Vanilla.Discussions.Edit'));
    }

    public function testMerge() {
        $permissions = new Permissions([
            'Garden.SignIn.Allow',
            'Vanilla.Discussions.Add' => [10]
        ]);
        $additionalPermissions = new Permissions([
            'Garden.Profiles.View',
            'Vanilla.Discussions.Add' => [20, 30]
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
            'Garden.Discussions.Add' => [1, 2, 3]
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
            'Vanilla.Discussions.Edit' => [10]
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
            'Vanilla.Comments.Edit' => [10]
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
}
