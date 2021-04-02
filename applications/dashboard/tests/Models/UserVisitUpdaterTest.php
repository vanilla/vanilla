<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Models;

use Garden\Events\BulkUpdateEvent;
use Garden\Events\ResourceEvent;
use PHPUnit\Framework\Constraint\IsInstanceOf;
use PHPUnit\Framework\Constraint\IsType;
use Psr\SimpleCache\CacheInterface;
use Vanilla\Cache\CacheCacheAdapter;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Models\UserVisitUpdater;
use Vanilla\Formatting\DateTimeFormatter;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for user visit updates.
 */
class UserVisitUpdaterTest extends AbstractAPIv2Test {
    use EventSpyTestTrait;
    use UsersAndRolesApiTestTrait;
    use CommunityApiTestTrait;
    use SetupTraitsTrait;

    /**
     * Setup.
     */
    public static function setupBeforeClass(): void {
        CurrentTimeStamp::mockTime('Jan 1 2019');
        parent::setupBeforeClass();
    }

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
    }

    /**
     * @return UserVisitUpdater
     */
    private function visitUpdater(): UserVisitUpdater {
        return $this->container()->get(UserVisitUpdater::class);
    }

    /**
     * Test that date times in the database get updated.
     */
    public function testUpdateActiveDate() {
        $startTime = CurrentTimeStamp::mockTime('Dec 19 2019');
        $user = $this->createUser();
        $userID = $user['userID'];
        $this->api()->setUserID($userID);
        $this->assertDatesEqual($startTime, $user['dateLastActive']);

        $updatedTime = CurrentTimeStamp::mockTime('Dec 20 2019');
        $this->visitUpdater()->updateVisit($userID);
        $user = $this->api()->get("/users/$userID")->getBody();
        $this->assertDatesEqual($updatedTime, $user['dateLastActive']);

        // Ensure that events are fired.
        $this->assertHandlerCalled('userModel_visit_handler', [
            new IsInstanceOf(\UserModel::class),
            new IsType('array'),
        ]);

        // Ensure that events are fired.
        $calledArgs = $this->assertHandlerCalled('userModel_updateVisit_handler', [
            new IsInstanceOf(\UserModel::class),
            new IsType('array'),
        ]);

        $this->assertEquals([
            'DateLastActive' => DateTimeFormatter::timeStampToDateTime($updatedTime->getTimestamp()),
            'CountVisits' => 1,
        ], $calledArgs[1]['Fields']);
    }

    /**
     * Test that multiple user updates are batched together.
     */
    public function testBulkDispatch() {
        $startTime = CurrentTimeStamp::mockTime('Dec 21 2019');
        $cache = new \Gdn_Dirtycache();
        $cacheAdapter = new CacheCacheAdapter($cache);
        $this->container()->setInstance(CacheInterface::class, $cacheAdapter);

        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $user3 = $this->createUser();

        $this->clearDispatchedEvents();

        $day2Time = CurrentTimeStamp::mockTime('Dec 22 2019');
        $updater = $this->visitUpdater();

        // First call should flush out currently active users.
        $updater->updateVisit($user1['userID']);
        $this->assertBulkEventDispatched(new BulkUpdateEvent(
            'user',
            [
                'userID' => [
                    $user1['userID']
                ],
            ],
            [
                'dateLastActive' => $day2Time->format(DATE_RFC3339)
            ]
        ));

        $this->clearDispatchedEvents();
        $updateTime = CurrentTimeStamp::mockTime($day2Time->modify("+3 minutes"));
        // These updates should be queued and there should be no dispatched events.
        $updater->updateVisit($user1['userID']);
        $updater->updateVisit($user2['userID']);
        $this->assertNoEventsDispatched();

        // After we pass our threshold all items queued from the start are pushed.
        $updateTime = CurrentTimeStamp::mockTime($updateTime->modify("+20 minutes"));
        $updater->updateVisit($user3['userID']);
        $this->assertBulkEventDispatched(new BulkUpdateEvent(
            'user',
            [
                'userID' => array_column([$user1, $user2, $user3], 'userID')
            ],
            [
                'dateLastActive' => $updateTime->format(DATE_RFC3339)
            ]
        ));
    }

    /**
     * Test that restricted property updates don't fire events.
     */
    public function testRestrictedPropertiesEvents() {
        $user = $this->createUser();
        $userID = $user["userID"];

        $this->clearDispatchedEvents();

        // 1. Giving a user points shouldn't fire and event.
        $this->givePoints($user["userID"], 10);

        $this->assertEventNotDispatched(["type" => "user", "action" => ResourceEvent::ACTION_UPDATE]);
        $this->assertDirtyRecordInserted("user", $userID);

        // 2. Updating a users discussion count shouldn't an fire event.
        $currentApiUser = $this->api()->getUserID();
        $this->api()->setUserID($userID);

        $this->createDiscussion();

        $this->assertEventNotDispatched(["type" => "user", "action" => ResourceEvent::ACTION_UPDATE]);
        $this->assertDirtyRecordInserted("user", $userID);

        $this->api()->setUserID($currentApiUser);
    }

    /**
     * Test handler.
     *
     * @param array $args
     */
    public function userModel_visit_handler(...$args) {
        $this->handlerCalled(__FUNCTION__, $args);
    }

    /**
     * Test handler.
     *
     * @param array $args
     */
    public function userModel_updateVisit_handler(...$args) {
        $this->handlerCalled(__FUNCTION__, $args);
    }

    /**
     * Assert that 2 dates are equal.
     *
     * @param \DateTimeInterface|string|int $expected
     * @param \DateTimeInterface|string|int $actual
     */
    private function assertDatesEqual($expected, $actual) {
        $this->assertEquals(
            CurrentTimeStamp::coerceDateTime($expected)->format(DATE_RFC3339),
            CurrentTimeStamp::coerceDateTime($actual)->format(DATE_RFC3339)
        );
    }
}
