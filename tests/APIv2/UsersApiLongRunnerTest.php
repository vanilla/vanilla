<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Web\Exception\ForbiddenException;
use Vanilla\Scheduler\Driver\LocalDriverSlip;
use Vanilla\Scheduler\Job\JobExecutionProgress;
use Vanilla\Scheduler\Job\LongRunnerJob;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\LogModelTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/users endpoints.
 */
class UsersApiLongRunnerTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    use EventSpyTestTrait;
    use UsersAndRolesApiTestTrait;
    use ExpectExceptionTrait;
    use LogModelTestTrait;

    public array $allUserIDs;

    public array $addRoleIDs;

    public array $removeRoleIDs;

    public array $addReplacementRoleIDs;
    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $user3 = $this->createUser();
        $user4 = $this->createUser();
        $user5 = $this->createUser();
        $user6 = $this->createUser();
        $allUsers = [$user1, $user2, $user3, $user4, $user5, $user6];
        $this->allUserIDs = array_column($allUsers, "userID");
        $assignedRoleIDs = $this->userModel->getRoleIDs($this->allUserIDs[0]);
        $role1 = $this->createRole();
        $role2 = $this->createRole();
        $role3 = $this->createRole();
        $role4 = $this->createRole();
        $role5 = $this->createRole();
        $role6 = $this->createRole();
        $allRoles = [$role1, $role2, $role3, $role4, $role5, $role6];
        $allRoleIDs = array_column($allRoles, "roleID");
        $this->addRoleIDs = array_slice($allRoleIDs, 0, 2);
        $this->removeRoleIDs = array_merge(array_slice($allRoleIDs, 2, 2), $assignedRoleIDs);
        $this->addReplacementRoleIDs = array_slice($allRoleIDs, 4, 2);

        for ($i = 0; $i < 6; $i++) {
            $this->userModel->addRoles($this->allUserIDs[$i], [$allRoleIDs[$i]], false);
        }
    }

    /**
     * Test that our progress is tracked when full update multiple items.
     */
    public function testBulkRoleModifyProgress()
    {
        $scheduler = $this->getScheduler();

        $scheduler->pause();
        $response = $this->api()->patch("/users/bulk-role-assignment?longRunnerMode=async", [
            "userIDs" => $this->allUserIDs,
            "addRoleIDs" => $this->addRoleIDs,
            "removeRoleIDs" => $this->removeRoleIDs,
            "addReplacementRoleIDs" => $this->addReplacementRoleIDs,
        ]);
        $this->assertEquals(202, $response->getStatusCode());

        // Naturally this should all happen automatically, but because we can't use timeouts safely in tests
        // We've paused the scheduler and will progress the job manually.

        // Grab thejob out of the container.
        /** @var LocalDriverSlip $driverSlip */
        $driverSlip = $scheduler->getTrackingSlips()[0]->getDriverSlip();
        $this->assertInstanceOf(LocalDriverSlip::class, $driverSlip);

        /** @var LongRunnerJob $job */
        $job = $driverSlip->getJob();

        $this->assertInstanceOf(LongRunnerJob::class, $job);

        // Run until we get our first progress.
        $generator = $job->runIterator();

        /** @var JobExecutionProgress $progress */
        $generator->current();

        for ($i = 0; $i < 6; $i++) {
            $assignedRoleIDs = $this->userModel->getRoleIDs($this->allUserIDs[$i]);

            $this->assertTrue(in_array($this->addRoleIDs[0], $assignedRoleIDs));
            $this->assertTrue(in_array($this->addRoleIDs[1], $assignedRoleIDs));
            $this->assertTrue(!in_array($this->removeRoleIDs[0], $assignedRoleIDs));
            $this->assertTrue(!in_array($this->removeRoleIDs[1], $assignedRoleIDs));
        }
    }

    /**
     * Test that our progress is tracked when adding roles to multiple items.
     */
    public function testBulkRoleModifyProgressOnlyAdd()
    {
        $scheduler = $this->getScheduler();

        $scheduler->pause();
        $response = $this->api()->patch("/users/bulk-role-assignment?longRunnerMode=async", [
            "userIDs" => $this->allUserIDs,
            "addRoleIDs" => $this->addRoleIDs,
        ]);
        $this->assertEquals(202, $response->getStatusCode());

        // Naturally this should all happen automatically, but because we can't use timeouts safely in tests
        // We've paused the scheduler and will progress the job manually.

        // Grab thejob out of the container.
        /** @var LocalDriverSlip $driverSlip */
        $driverSlip = $scheduler->getTrackingSlips()[0]->getDriverSlip();
        $this->assertInstanceOf(LocalDriverSlip::class, $driverSlip);

        /** @var LongRunnerJob $job */
        $job = $driverSlip->getJob();

        $this->assertInstanceOf(LongRunnerJob::class, $job);

        // Run until we get our first progress.
        $generator = $job->runIterator();

        /** @var JobExecutionProgress $progress */
        $generator->current();
        for ($i = 0; $i < 6; $i++) {
            $assignedRoleIDs = $this->userModel->getRoleIDs($this->allUserIDs[$i]);

            $this->assertTrue(in_array($this->addRoleIDs[0], $assignedRoleIDs));
            $this->assertTrue(in_array($this->addRoleIDs[1], $assignedRoleIDs));
        }
    }

    /**
     * Test that our progress is tracked when we remove roles from multiple items.
     */
    public function testBulkRoleModifyProgressRemove()
    {
        $scheduler = $this->getScheduler();

        $scheduler->pause();
        $response = $this->api()->patch("/users/bulk-role-assignment?longRunnerMode=async", [
            "userIDs" => $this->allUserIDs,
            "removeRoleIDs" => $this->removeRoleIDs,
            "addReplacementRoleIDs" => $this->addReplacementRoleIDs,
        ]);
        $this->assertEquals(202, $response->getStatusCode());

        // Naturally this should all happen automatically, but because we can't use timeouts safely in tests
        // We've paused the scheduler and will progress the job manually.

        // Grab thejob out of the container.
        /** @var LocalDriverSlip $driverSlip */
        $driverSlip = $scheduler->getTrackingSlips()[0]->getDriverSlip();
        $this->assertInstanceOf(LocalDriverSlip::class, $driverSlip);

        /** @var LongRunnerJob $job */
        $job = $driverSlip->getJob();

        $this->assertInstanceOf(LongRunnerJob::class, $job);

        // Run until we get our first progress.
        $generator = $job->runIterator();

        /** @var JobExecutionProgress $progress */
        $generator->current();

        for ($i = 0; $i < 6; $i++) {
            $assignedRoleIDs = $this->userModel->getRoleIDs($this->allUserIDs[$i]);
            $this->assertTrue(!in_array($this->removeRoleIDs[0], $assignedRoleIDs));
            $this->assertTrue(!in_array($this->removeRoleIDs[1], $assignedRoleIDs));

            if (in_array($i, [2, 3])) {
                $this->assertTrue(in_array($this->addReplacementRoleIDs[0], $assignedRoleIDs));
                $this->assertTrue(in_array($this->addReplacementRoleIDs[1], $assignedRoleIDs));
            }
        }
    }

    /**
     * Test that our progress is tracked when we remove roles without needing replacement Role from multiple items.
     */
    public function testBulkRoleModifyProgressRemoveNoReplacement()
    {
        $scheduler = $this->getScheduler();

        $this->userModel->addRoles($this->allUserIDs[0], $this->removeRoleIDs, false);
        $this->userModel->addRoles($this->allUserIDs[1], $this->removeRoleIDs, false);
        $this->userModel->addRoles($this->allUserIDs[2], $this->addRoleIDs, false);
        $this->userModel->addRoles($this->allUserIDs[3], $this->addRoleIDs, false);
        $scheduler->pause();
        $response = $this->api()->patch("/users/bulk-role-assignment?longRunnerMode=async", [
            "userIDs" => $this->allUserIDs,
            "removeRoleIDs" => array_slice($this->removeRoleIDs, 0, 2),
        ]);
        $this->assertEquals(202, $response->getStatusCode());

        // Naturally this should all happen automatically, but because we can't use timeouts safely in tests
        // We've paused the scheduler and will progress the job manually.

        // Grab thejob out of the container.
        /** @var LocalDriverSlip $driverSlip */
        $driverSlip = $scheduler->getTrackingSlips()[0]->getDriverSlip();
        $this->assertInstanceOf(LocalDriverSlip::class, $driverSlip);

        /** @var LongRunnerJob $job */
        $job = $driverSlip->getJob();

        $this->assertInstanceOf(LongRunnerJob::class, $job);

        // Run until we get our first progress.
        $generator = $job->runIterator();

        /** @var JobExecutionProgress $progress */
        $generator->current();

        for ($i = 0; $i < 6; $i++) {
            $assignedRoleIDs = $this->userModel->getRoleIDs($this->allUserIDs[$i]);
            $this->assertTrue(!in_array($this->removeRoleIDs[0], $assignedRoleIDs));
            $this->assertTrue(!in_array($this->removeRoleIDs[1], $assignedRoleIDs));
        }
    }

    /**
     * Test that our progress fails validation when we remove roles from multiple items and not add any.
     */
    public function testBulkRoleModifyProgressRemoveOnlyThrowsValidationError()
    {
        $scheduler = $this->getScheduler();

        $scheduler->pause();
        $this->expectExceptionCode(400);
        $this->expectExceptionMessage("You must choose a replacement role for orphaned users.");
        $this->api()->patch("/users/bulk-role-assignment", [
            "userIDs" => $this->allUserIDs,
            "removeRoleIDs" => $this->removeRoleIDs,
        ]);
    }

    /**
     * Test that the user has the "Garden.Users.Edit" permission for bulk role edit.
     */
    public function testPatchPermissionError()
    {
        $user = $this->createUser([
            "name" => __FUNCTION__,
            "password" => "password1234",
            "roleID" => [\RoleModel::GUEST_ID],
        ]);

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage("Permission Problem");
        $this->runWithUser(function () use ($user) {
            $response = $this->api()->patch("/users/bulk-role-assignment?longRunnerMode=async", [
                "userIDs" => $this->allUserIDs,
                "removeRoleIDs" => $this->removeRoleIDs,
            ]);
        }, $user);
    }
}
