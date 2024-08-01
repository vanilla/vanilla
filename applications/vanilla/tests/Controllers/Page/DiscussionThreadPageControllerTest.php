<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Controllers\Page;

use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use VanillaTests\Fixtures\Request;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test the custom layout discussion thread page.
 */
class DiscussionThreadPageControllerTest extends SiteTestCase
{
    use CommunityApiTestTrait;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $config = Gdn::getContainer()->get(ConfigurationInterface::class);
        $config->saveToConfig("Feature.customLayout.discussionThread.Enabled", true);
        $config->saveToConfig("Vanilla.Comments.PerPage", 2);
        parent::setUp();
    }

    /**
     * Test valid routes for getting a discussion thread.
     *
     * @return void
     */
    public function testCustomLayoutDiscussionThread()
    {
        $discussion = $this->createDiscussion();

        $comment1 = $this->createComment(["body" => "I am the first comment"]);
        CurrentTimeStamp::mockTime("+1 minute");
        $comment2 = $this->createComment(["body" => "I am the second comment"]);
        CurrentTimeStamp::mockTime("+1 day");
        $comment3 = $this->createComment(["body" => "I am the third comment"]);

        $dispatcher = Gdn::getContainer()->get(\Garden\Web\Dispatcher::class);

        // Page 1 should have only the first 2 comments.
        $page1 = $dispatcher->dispatch(new Request("/discussion/{$discussion["discussionID"]}"))->getData();

        $this->assertStringContainsString($comment1["body"], $page1);
        $this->assertStringContainsString($comment2["body"], $page1);
        $this->assertStringNotContainsString($comment3["body"], $page1);

        // Page 2 should have only the 3rd comment.
        $page2 = $dispatcher->dispatch(new Request("/discussion/{$discussion["discussionID"]}/p2"))->getData();

        $this->assertStringContainsString($comment3["body"], $page2);
        $this->assertStringNotContainsString($comment2["body"], $page2);
        $this->assertStringNotContainsString($comment1["body"], $page2);

        // A permalink should take you to the page that the comment is on.
        $permalink = $dispatcher->dispatch(new Request("/discussion/comment/{$comment3["commentID"]}"))->getData();

        $this->assertStringContainsString($comment3["body"], $permalink);
        $this->assertStringNotContainsString($comment2["body"], $permalink);
        $this->assertStringNotContainsString($comment1["body"], $permalink);
    }
}
