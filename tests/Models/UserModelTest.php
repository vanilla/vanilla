<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use VanillaTests\SiteTestTrait;
use Garden\EventManager;
use Vanilla\Dashboard\Events\UserEvent;

/**
 * Test {@link UserModel}.
 */
class UserModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait {
        setupBeforeClass as baseSetupBeforeClass;
    }

    /** @var UserEvent */
    private $lastEvent;

    /**
     * @var \UserModel
     */
    private $userModel;

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::baseSetupBeforeClass();
    }

    /**
     * Setup
     */
    public function setup(): void {
        $this->userModel = $this->container()->get(\UserModel::class);
        // Make event testing a little easier.
        $this->container()->setInstance(self::class, $this);
        $this->lastEvent = null;
        /** @var EventManager */
        $eventManager = $this->container()->get(EventManager::class);
        $eventManager->unbindClass(self::class);
        $eventManager->addListenerMethod(self::class, "handleUserEvent");
    }

    /**
     * A test listener that increments the counter.
     *
     * @param TestEvent $e
     * @return TestEvent
     */
    public function handleUserEvent(UserEvent $e): UserEvent {
        $this->lastEvent = $e;
        return $e;
    }

    /**
     * Verify delete event dispatched during deletion.
     *
     * @return void
     */
    public function testDeleteEventDispatched(): void {
        $user = [
            "Name" => "testuser",
            "Email" => "testuser@example.com",
            "Password" => "vanilla"
        ];

        $userID = $this->userModel->save($user);
        $this->userModel->deleteID($userID);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_DELETE, $this->lastEvent->getAction());
    }

    /**
     * Verify insert event dispatched during save.
     *
     * @return void
     */
    public function testSaveInsertEventDispatched(): void {
        $user = [
            "Name" => "testuser2",
            "Email" => "testuser2@example.com",
            "Password" => "vanilla"
        ];
        $this->userModel->save($user);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_INSERT, $this->lastEvent->getAction());
    }

    /**
     * Verify update event dispatched during save.
     *
     * @return void
     */
    public function testSaveUpdateEventDispatched(): void {
        $user = [
            "Name" => "testuser3",
            "Email" => "testuser3@example.com",
            "Password" => "vanilla"
        ];
        $userUpdate = [
            "Name" => "testuser3",
            "Email" => "testuser4@example.com",
            "Password" => "vanilla"
        ];
        $userID = $this->userModel->save($user);
        $user = (array)$this->userModel->getID($userID);
        $userUpdate['UserID'] = $user['UserID'];
        $this->userModel->save($userUpdate);
        $this->assertInstanceOf(UserEvent::class, $this->lastEvent);
        $this->assertEquals(UserEvent::ACTION_UPDATE, $this->lastEvent->getAction());
    }

    /**
     * Test searching for users by a role keyword.
     */
    public function testSearchByRole(): void {
        $roles = $this->getRoles();
        $adminRole = $roles["Administrator"];

        // Make sure we have at least one non-admin.
        $this->userModel->save([
            "Name" => __FUNCTION__,
            "Email" => __FUNCTION__ . "@example.com",
            "Password" => "vanilla",
            "RoleID" => $roles["Member"],
        ]);

        $users = $this->userModel->search("Administrator")->resultArray();
        \RoleModel::setUserRoles($users);

        $result = true;
        foreach ($users as $user) {
            $userRoles = array_keys($user["Roles"]);
            if (!in_array($adminRole, $userRoles)) {
                $result = false;
                break;
            }
        }

        $this->assertTrue($result, "Failed to only return users in the specific role.");
    }
}
