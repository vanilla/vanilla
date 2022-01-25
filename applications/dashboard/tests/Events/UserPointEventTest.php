<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard\Events;

use Garden\Events\ResourceEvent;
use ReactionModel;
use Vanilla\Badges\Events\UserBadgeEvent;
use Vanilla\Dashboard\Events\UserPointEvent;
use VanillaTests\APIv2\AbstractBadgesSubResource;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Tests for UserPointEvent.
 */
class UserPointEventTest extends SiteTestCase {

    use EventSpyTestTrait, \VanillaTests\APIv2\QnaApiTestTrait, UsersAndRolesApiTestTrait;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array {
        return ["vanilla", "qna", "reactions"];
    }

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
    }
}
