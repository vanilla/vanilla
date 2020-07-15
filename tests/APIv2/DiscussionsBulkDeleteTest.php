<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use Garden\Events\ResourceEvent;
use Vanilla\Http\InternalClient;
use Vanilla\Http\InternalRequest;
use Vanilla\TranslationsApi\Models\ResourceModel;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test that deleting a discussion delets it's comments.
 */
class DiscussionsBulkDeleteTest extends AbstractAPIv2Test {

    use CommunityApiTestTrait;
    use EventSpyTestTrait;

    /**
     * @inheritdoc
     */
    public function setUp(): void {
        parent::setUp();
        \Gdn::sql()->truncate('Log');
    }

    /**
     * Test that deleting a discussion deletes all comments and logs them with the correct transactionID.
     */
    public function testDeleteDiscussion() {
        $this->container()->rule(InternalRequest::class)
            ->addCall('setHeader', [LogTransactionMiddleware::HEADER_NAME, 1000]);

        $category = $this->createCategory();
        $discussion = $this->createDiscussion();
        $comment1 = $this->createComment();
        $comment2 = $this->createComment();
        $comment3 = $this->createComment();

        $this->api()->delete("/discussions/{$discussion['discussionID']}");

        /** @var \LogModel $logModel */
        $logModel = $this->container()->get(\LogModel::class);
        $logs = $logModel->getWhere([
            'TransactionLogID' => 1000,
        ]);
        $this->assertCount(4, $logs);

        // Comment count
        $this->assertCount(0, $this->api()->get('/comments', ['insertUserID' => $this->api()->getUserID()])->getBody());

        // Restoration off of our transactionID.
        $logModel->restore($logs[0]);
        $this->assertCount(0, $logModel->getWhere([ 'TransactionLogID' => 1000 ]));
        $this->assertCount(3, $this->api()->get('/comments', ['insertUserID' => $this->api()->getUserID()])->getBody());


        $this->assertEventsDispatched([
            $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment1),
            $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment2),
            $this->expectedResourceEvent("comment", ResourceEvent::ACTION_DELETE, $comment3),
            $this->expectedResourceEvent("discussion", ResourceEvent::ACTION_DELETE, $discussion),
        ], ['commentID', 'discussionID', 'name']);
    }
}
