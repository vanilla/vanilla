<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SiteTestCase;
use RoleModel;

/**
 * Tests for the `RoleModel`.
 */
class RoleModelTest extends SiteTestCase {
    /**
     * @var \Gdn_Cache
     */
    private $cache;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->cache = $this->enableCaching();

        $this->createUserFixtures();
    }

    /**
     * Make sure the cache reads as enabled for testing purposes.
     */
    public function testCacheActiveEnabled(): void {
        $this->assertTrue(\Gdn_Cache::activeEnabled());
    }

    /**
     * The default role types should be present after an install.
     */
    public function testDefaultRoleTypes(): void {
        $types = RoleModel::getDefaultTypes();
        $roles = RoleModel::getAllDefaultRoles();
        foreach ($types as $type => $name) {
            $this->assertArrayHasKey($type, $roles);
            $typeRoleIDs = $roles[$type];
            $typeRoleIDs2 = array_column($this->roleModel->getByType($type)->resultArray(), 'RoleID');
            $this->assertSame($typeRoleIDs, $typeRoleIDs2);
        }
    }

    /**
     * Test adding a role and getting it through the static roles method.
     */
    public function testInsertRole(): void {
        // This line will prime the cache.
        RoleModel::roles();

        $role = ['Name' => __FUNCTION__];
        $id = $this->roleModel->insert($role);
        $this->assertNotFalse($id);

        $currentRole = RoleModel::roles($id);
        $this->assertArraySubsetRecursive($role, $currentRole);
        $this->assertIsArray($currentRole);

        $allRoles = RoleModel::roles();
        $this->assertArrayHasKey($id, $allRoles);
    }

    /**
     * Test updating a role with cache busting.
     */
    public function testUpdateRole(): void {
        RoleModel::roles();
        $role = ['Name' => __FUNCTION__];
        $id = $this->roleModel->insert($role);
        RoleModel::roles();
        $role['Name'] = __FUNCTION__.' Updated';
        $this->roleModel->update($role, ['RoleID' => $id]);

        $currentRole = RoleModel::roles($id);
        $this->assertArraySubsetRecursive($role, $currentRole);
    }

    /**
     * Saving a role should work like inserting and updating.
     */
    public function testSaveRole(): void {
        $role = ['Name' => __FUNCTION__];
        $id = $this->roleModel->save($role);
        $newRole = ['RoleID' => $id, 'Name' => __FUNCTION__.' Saved'];
        RoleModel::roles();
        $this->roleModel->save($newRole);
        $this->assertArraySubsetRecursive($newRole, RoleModel::roles($id));
    }

    /**
     * A non-existent role should return null or be forced.
     */
    public function testGetNonExistentRole(): void {
        $role = RoleModel::roles(PHP_INT_MAX);
        $this->assertNull($role);

        $role = RoleModel::roles(PHP_INT_MAX, true);
        $this->assertSame($role['RoleID'], PHP_INT_MAX);
    }

    /**
     * A role marked as personal info shouldn't be viewed.
     */
    public function testPersonalInfo(): void {
        $role = ['Name' => __FUNCTION__, 'PersonalInfo' => true];
        $id = $this->roleModel->insert($role);
        $this->userModel->addRoles($this->adminID, [$id], false);

        $rolesAsAdmin = $this->roleModel->getPublicUserRoles($this->adminID, 'RoleID');
        \Gdn::session()->start($this->memberID);
        $rolesAsMember = $this->roleModel->getPublicUserRoles($this->adminID, 'RoleID');

        $this->assertContains($id, $rolesAsAdmin);
        $this->assertNotContains($id, $rolesAsMember);
    }

    /**
     * You should be able to replace membership in a deleted role with a new one.
     */
    public function testDeleteAndReplaceRole(): void {
        $role = ['Name' => __FUNCTION__];
        $id = (int)$this->roleModel->insert($role);
        $memberRoles = $this->userModel->getRoleIDs($this->memberID);
        $this->assertNotEmpty($memberRoles);
        foreach ($memberRoles as $toDelete) {
            $this->roleModel->deleteID($toDelete, ['newRoleID' => $id]);
        }
        $newMemberRoles = $this->userModel->getRoleIDs($this->memberID);
        $this->assertSame([$id], $newMemberRoles);
    }
}
