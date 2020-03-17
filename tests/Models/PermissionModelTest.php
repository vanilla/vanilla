<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use PHPUnit\Framework\TestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the `PermissionModel` class.
 */
class PermissionModelTest extends TestCase {
    use SiteTestTrait;

    /**
     * @var \PermissionModel
     */
    private $permissionModel;

    /**
     *  Test setup.
     */
    public function setUp(): void {
        parent::setUp();

        $this->permissionModel = $this->container()->get(\PermissionModel::class);
    }

    /**
     * Test the basic admin user permissions after an installation.
     */
    public function testGetPermissionsByUserBasic() {
        $permissions = $this->permissionModel->getPermissionsByUser(self::$siteInfo['adminUserID']);
        $expected = [
            0 => 'Garden.Email.View',
            1 => 'Garden.Settings.Manage',
            2 => 'Garden.Settings.View',
            3 => 'Garden.SignIn.Allow',
            4 => 'Garden.Users.Add',
            5 => 'Garden.Users.Edit',
            6 => 'Garden.Users.Delete',
            7 => 'Garden.Users.Approve',
            8 => 'Garden.Activity.Delete',
            9 => 'Garden.Activity.View',
            10 => 'Garden.Profiles.View',
            11 => 'Garden.Profiles.Edit',
            12 => 'Garden.Curation.Manage',
            13 => 'Garden.Moderation.Manage',
            14 => 'Garden.PersonalInfo.View',
            15 => 'Garden.AdvancedNotifications.Allow',
            16 => 'Garden.Community.Manage',
            17 => 'Garden.Uploads.Add',
            18 => 'Vanilla.Discussions.View',
            19 => 'Vanilla.Discussions.Add',
            20 => 'Vanilla.Discussions.Edit',
            21 => 'Vanilla.Discussions.Announce',
            22 => 'Vanilla.Discussions.Sink',
            23 => 'Vanilla.Discussions.Close',
            24 => 'Vanilla.Discussions.Delete',
            25 => 'Vanilla.Comments.Add',
            26 => 'Vanilla.Comments.Edit',
            27 => 'Vanilla.Comments.Delete',
            28 => 'Conversations.Conversations.Add',
            'Vanilla.Discussions.View' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Add' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Edit' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Announce' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Sink' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Close' =>
                [
                    0 => -1,
                ],
            'Vanilla.Discussions.Delete' =>
                [
                    0 => -1,
                ],
            'Vanilla.Comments.Add' =>
                [
                    0 => -1,
                ],
            'Vanilla.Comments.Edit' =>
                [
                    0 => -1,
                ],
            'Vanilla.Comments.Delete' =>
                [
                    0 => -1,
                ],
        ];

        $this->assertSame($expected, $permissions);
    }

    /**
     * Test the basic install permissions for the guest user.
     */
    public function testGetPermissionsByUserGuest() {
        $permissions = $this->permissionModel->getPermissionsByUser(0);
        $expected = [
            0 => 'Garden.Activity.View',
            1 => 'Garden.Profiles.View',
            2 => 'Vanilla.Discussions.View',
            'Vanilla.Discussions.View' =>
                [
                    0 => -1,
                ],
        ];

        $this->assertSame($expected, $permissions);
    }
}
