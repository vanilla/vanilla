<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Events;

use ReactionModel;
use Vanilla\Dashboard\Events\UserPointEvent;
use VanillaTests\Analytics\SpyingAnalyticsTestTrait;
use VanillaTests\APIv2\QnaApiTestTrait;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for UserPointEvent.
 */
class UserPointEventTest extends SiteTestCase {
    use EventSpyTestTrait;
    use SpyingAnalyticsTestTrait;
    use QnaApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["vanillaanalytics", "qna", "reactions"];

    /**
     * Setup routine, run before each test case.
     */
    public function setUp(): void {
        parent::setUp();
        ReactionModel::$ReactionTypes = null;
        \Gdn::config()->saveToConfig('QnA.Points.Enabled', true);
    }

    /**
     * Test that a UserPointEvent is dispatched when user receives points from answering a question.
     */
    public function testEventUserPointQnA() {
        $this->createQuestion([
            'categoryID' => -1,
            'name' => 'Question 1',
            'body' => 'Question 1'
        ]);

        $user = $this->createUser();
        $this->runWithUser(function () {
            $this->createAnswer();
        }, $user);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'user',
                UserPointEvent::ACTION_USERPOINT_ADD,
                []
            ),
            []
        );

        // Assert that the tracked event data validates against its catalog.
        $trackedEvent = $this->getTracker()->getTrackedEventLike("point", "user_point_add");
        $trackedEvent->assertMatchesCatalog("point");
    }

    /**
     * Test that a UserPointEvent is dispatched when user receives points from a reaction.
     */
    public function testEventUserPointReaction() {
        ReactionModel::$ReactionTypes = null;
        $this->createDiscussion();

        $this->api()->post("/discussions/$this->lastInsertedDiscussionID/reactions", [
            'reactionType' => 'Like'
        ]);

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'user',
                UserPointEvent::ACTION_USERPOINT_ADD,
                []
            ),
            []
        );

        $this->api->delete("/discussions/$this->lastInsertedDiscussionID/reactions");

        $this->assertEventDispatched(
            $this->expectedResourceEvent(
                'user',
                UserPointEvent::ACTION_USERPOINT_SUB,
                []
            ),
            []
        );

        $trackedEventAdd = $this->getTracker()->getTrackedEventLike('point', 'user_point_add');
        $trackedEventDelete = $this->getTracker()->getTrackedEventLike('point', 'user_point_add');

        // Assert that the tracked events data validates against their catalog.
        $trackedEventAdd->assertMatchesCatalog("point");
        $trackedEventDelete->assertMatchesCatalog("point");
    }
}
