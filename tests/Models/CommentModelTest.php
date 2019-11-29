<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use CommentModel;
use DiscussionModel;
use VanillaTests\SiteTestTrait;

/**
 * Test {@link CommentModel}.
 */
class CommentModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait {
        setupBeforeClass as baseSetupBeforeClass;
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void {
        self::baseSetupBeforeClass();

        // Test as an admin
        self::container()->get('Session')->start(self::$siteInfo['adminUserID']);
    }

    /**
     * Test the lookup method.
     */
    public function testLookup() {
        $commentModel = new CommentModel();
        $discussionModel = new DiscussionModel();

        $discussion = [
            'CategoryID' => 1,
            'Name' => 'Comment Lookup Test',
            'Body' => 'foo foo foo',
            'Format' => 'Text',
            'InsertUserID' => 1
        ];
        $discussionID = $discussionModel->save($discussion);

        $comment = [
            'DiscussionID' => $discussionID,
            'Body' => 'Hello world.',
            'Format' => 'Text'
        ];
        $commentID = $commentModel->save($comment);
        $this->assertNotFalse($commentID);

        $result = $commentModel->lookup(['CommentID' => $commentID] + $comment);
        $this->assertInstanceOf('Gdn_DataSet', $result);
        $this->assertEquals(1, $result->count());

        $row = $result->firstRow(DATASET_TYPE_ARRAY);
        $this->assertEquals($commentID, $row['CommentID']);
    }

    /**
     * Test inserting and updating a user's watch status of comments in a discussion.
     *
     * @return void
     */
    public function testSetWatch(): void {
        $commentModel = new CommentModel();
        $discussionModel = new DiscussionModel();

        $countComments = 5;
        $discussion = [
            "CategoryID" => 1,
            "Name" => "Comment Watch Test",
            "Body" => "foo bar baz",
            "Format" => "Text",
            "CountComments" => $countComments,
            "InsertUserID" => 1,
        ];

        // Confirm the initial state, so changes are easy to detect.
        $discussionID = $discussionModel->save($discussion);
        $discussion = $discussionModel->getID($discussionID);
        $this->assertNull(
            $discussion->CountCommentWatch,
            "Initial comment watch status not null."
        );

        // Create a comment watch status.
        $commentModel->setWatch($discussion, 10, 0, $discussion->CountComments);
        $discussionFirstVisit = $discussionModel->getID($discussionID);
        $this->assertSame(
            $discussionFirstVisit->CountComments,
            $discussionFirstVisit->CountCommentWatch,
            "Creating new comment watch status failed."
        );

        // Update an existing comment watch status.
        $updatedCountComments = $countComments + 1;
        $discussionModel->setField($discussionID, "CountComments", $updatedCountComments);
        $commentModel->setWatch($discussionFirstVisit, 10, 0, $updatedCountComments);
        $discussionSecondVisit = $discussionModel->getID($discussionID);
        $this->assertSame(
            $discussionSecondVisit->CountComments,
            $discussionSecondVisit->CountCommentWatch,
            "Updating comment watch status failed."
        );
    }
}
