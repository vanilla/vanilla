<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Reactions\Tests;

use CommentModel;
use DiscussionModel;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\SiteTestTrait;
use VanillaTests\EventSpyTestTrait;

use Garden\Events\ResourceEvent;

use VanillaTests\VanillaTestCase;

/**
 * Test reaction events are working.
 */
class ReactionsEventsTest extends VanillaTestCase
{
    use CommunityApiTestTrait, SiteTestTrait, SetupTraitsTrait, EventSpyTestTrait;

    /** @var \ReactionModel */
    private $reactionModel;

    /** @var \DiscussionModel */
    private $discussionModel;

    /** @var \CommentModel */
    private $commentModel;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array
    {
        return ["vanilla", "reactions"];
    }

    /**
     * Instantiate fixtures.
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpTestTraits();

        $this->container()->call(function (
            \ReactionModel $reactionModel,
            \DiscussionModel $discussionModel,
            \CommentModel $commentModel
        ) {
            $this->reactionModel = $reactionModel;
            $this->discussionModel = $discussionModel;
            $this->commentModel = $commentModel;
        });
    }

    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        self::setUpBeforeClassTestTraits();
    }

    /**
     * Tests event upon a reaction to a discussion.
     */
    public function testToggleUserTagDiscussionReactionEvent()
    {
        $this->assertTrue(true);

        $this->createDiscussion();

        $discussion = $this->discussionModel->getID($this->lastInsertedDiscussionID, DATASET_TYPE_ARRAY);

        $types = \ReactionModel::reactionTypes();
        $reaction = reset($types);

        $data = [
            "RecordType" => "Discussion",
            "RecordID" => $discussion["DiscussionID"],
            "TagID" => $reaction["TagID"],
            "UserID" => $this->api()->getUserID(),
        ];

        [$record] = $this->reactionModel->getRow("Discussion", $discussion["DiscussionID"]);

        $this->reactionModel->toggleUserTag($data, $record, $this->discussionModel);

        $this->assertEventDispatched(
            $this->expectedResourceEvent("reaction", ResourceEvent::ACTION_INSERT, [
                "tagID" => $reaction["TagID"],
                "recordName" => $discussion["Name"],
                "recordUrl" => DiscussionModel::discussionUrl($discussion),
            ])
        );
    }

    /**
     * Test that a reaction event is properly created and dispatched when a comment is reacted to.
     */
    public function testToggleUserTagCommentReactionEvent()
    {
        $discussion = $this->createDiscussion();
        $comment = $this->createComment();

        $types = \ReactionModel::reactionTypes();
        $reaction = reset($types);

        $data = [
            "RecordType" => "Comment",
            "RecordID" => $comment["commentID"],
            "TagID" => $reaction["TagID"],
            "UserID" => $this->api()->getUserID(),
        ];

        [$record] = $this->reactionModel->getRow("Comment", $comment["commentID"]);

        $this->reactionModel->toggleUserTag($data, $record, $this->commentModel);

        $this->assertEventDispatched(
            $this->expectedResourceEvent("reaction", ResourceEvent::ACTION_INSERT, [
                "tagID" => $reaction["TagID"],
                "recordName" => CommentModel::generateCommentName($discussion["name"]),
                "recordUrl" => CommentModel::commentUrl(ArrayUtils::pascalCase($comment)),
            ])
        );
    }
}
