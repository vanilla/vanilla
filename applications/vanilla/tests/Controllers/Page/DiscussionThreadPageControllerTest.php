<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Controllers\Page;

use Garden\Container\NotFoundException;
use Gdn;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Web\AbstractJsonLDItem;
use VanillaTests\Fixtures\Request;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\SiteTestCase;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the custom layout discussion thread page.
 */
class DiscussionThreadPageControllerTest extends SiteTestCase
{
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    private ConfigurationInterface $config;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        $this->config = Gdn::getContainer()->get(ConfigurationInterface::class);
        $this->enableFeature("customLayout.post");
        $this->config->saveToConfig("Vanilla.Comments.PerPage", 2);
        parent::setUp();
    }

    /**
     * Test content is what we expect for both flat/nested threadStyle.
     *
     * @return void
     */
    public function assertPageContent()
    {
        $discussion = $this->createDiscussion();

        CurrentTimeStamp::mockTime("2024-01-01");
        $comment1 = $this->createComment(["body" => "I am the first comment"]);
        CurrentTimeStamp::mockTime("2024-01-02");
        $comment2 = $this->createComment(["body" => "I am the second comment"]);
        CurrentTimeStamp::mockTime("2024-01-03");
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

    /**
     * Test valid routes for getting a discussion thread.
     *
     * @return void
     */
    public function testCustomLayoutDiscussionThread()
    {
        $this->assertPageContent();

        // even with "threadStyle" = "nested", when we are still getting expected content
        $this->assertPageContent();
    }

    /**
     * Test that when threaded discussion is enabled, We can still route through other actions without error.
     *
     * @return void
     * @throws NotFoundException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Schema\ValidationException
     */
    public function testCanAnnounceDiscussion()
    {
        $this->config->saveToConfig(FeatureFlagHelper::featureConfigKey("customLayout.home"), false);
        $this->config->saveToConfig("Routes.DefaultController", ["discussion", "Internal"]);
        $discussion = $this->createDiscussion();
        $result = $this->bessy()->getHtml(
            "/discussion/announce?discussionID={$discussion["discussionID"]}&DeliveryType=VIEW"
        );
        $result->assertContainsString("Announce");
        $result->assertContainsString("Where do you want to announce this discussion?");
        $formValues = $result->getFormValues();
        $this->assertArrayHasKey("Announce", $formValues);
        $this->assertEquals(0, $formValues["Announce"]);
    }

    /**
     * Test that permanlinks resolve top level comments only.
     *
     * @return void
     */
    public function testNestedPermalinks(): void
    {
        $this->runWithConfig(
            [
                "Vanilla.Comments.PerPage" => 1,
            ],
            function () {
                $dispatcher = Gdn::getContainer()->get(\Garden\Web\Dispatcher::class);

                CurrentTimeStamp::mockTime("2024-01-01");
                $discussion = $this->createDiscussion();
                $comment1 = $this->createComment();
                $comment1_1 = $this->createNestedComment($comment1);
                $comment2 = $this->createComment();
                CurrentTimeStamp::increment();
                $comment3 = $this->createComment();
                CurrentTimeStamp::increment();
                $comment3_1 = $this->createNestedComment($comment3);
                $comment3_1_1 = $this->createNestedComment($comment3_1, [
                    "body" => "3_1_1 comment",
                ]);
                CurrentTimeStamp::increment();
                $comment4 = $this->createComment();

                $commentModel = \Gdn::getContainer()->get(\CommentModel::class);

                $comment3_1Page = $commentModel->getCommentThreadPage($comment3_1);
                $this->assertEquals(3, $comment3_1Page);

                $comment4Page = $commentModel->getCommentThreadPage($comment4);
                $this->assertEquals(4, $comment4Page);

                // Comment 3 should be on the third page.
                $html = $dispatcher
                    ->dispatch(new Request("/discussion/comment/{$comment3_1_1["commentID"]}"))
                    ->getData();

                // We should have comment 3_1 visible.
                $this->assertStringContainsString($comment3_1["body"], $html);
            }
        );
    }

    /**
     * Test that redirect discussions redirect.
     *
     * @return void
     * @throws NotFoundException
     */
    public function testRedirectDiscussion()
    {
        $cat1 = $this->createCategory();
        $discussion1 = $this->createDiscussion();
        $cat2 = $this->createCategory();
        $this->api()->patch("/discussions/move", [
            "discussionIDs" => [$discussion1["discussionID"]],
            "addRedirects" => true,
            "categoryID" => $cat2["categoryID"],
        ]);

        $this->api()
            ->get("/discussions", ["categoryID" => $cat2["categoryID"]])
            ->assertSuccess()
            ->assertCount(1)
            ->assertJsonArrayContains(
                [
                    "discussionID" => $discussion1["discussionID"],
                ],
                "Expected cat2 to contain discussion1 after it was moved."
            );

        $redirectDisc = $this->api()
            ->get("/discussions", ["categoryID" => $cat1["categoryID"]])
            ->assertSuccess()
            ->assertCount(1)
            ->assertJsonArray()
            ->getBody()[0];

        $this->assertEquals("redirect", $redirectDisc["type"]);

        // Hydrating a layout for this discussion should result in a redirect.
        $hydrateEndpoint = $this->api()
            ->get("/layouts/lookup-hydrate", [
                "layoutViewType" => "post",
                "recordType" => "discussion",
                "recordID" => $redirectDisc["discussionID"],
                "params" => ["discussionID" => $redirectDisc["discussionID"]],
            ])
            ->assertJsonObject()
            ->getBody();

        $this->assertEquals($discussion1["url"], $hydrateEndpoint["redirectTo"]);
    }

    /**
     * Fixes https://higherlogic.atlassian.net/browse/VANS-2459
     *
     * @return void
     */
    public function testGuestCanAccess()
    {
        $discussion = $this->createDiscussion();

        $this->runWithUser(function () use ($discussion) {
            $this->api()
                ->get("/layouts/lookup-hydrate", [
                    "layoutViewType" => "post",
                    "recordType" => "discussion",
                    "recordID" => $discussion["discussionID"],
                    "params" => ["discussionID" => $discussion["discussionID"]],
                ])
                ->assertJsonObject()
                ->assertSuccess();
        }, \UserModel::GUEST_USER_ID);
    }

    /**
     * Test that the headline and description are properly set.
     *
     * @return void
     */
    public function testRichJsonLdDescription(): void
    {
        $this->createDiscussion([
            "name" => __FUNCTION__,
            "body" => "[{\"type\":\"p\",\"children\":[{\"text\":\"This is SUPER important for SEO!\"}]}]",
            "format" => "rich2",
        ]);
        $dispatcher = Gdn::getContainer()->get(\Garden\Web\Dispatcher::class);
        $response = $dispatcher->dispatch(new Request("/discussion/$this->lastInsertedDiscussionID"))->getData();

        // Extract and process the JsonLdDescription
        preg_match('~<script type="application/ld\+json">(.*?)</script>~s', $response, $rawJson);
        $this->assertNotEmpty($rawJson[1], "Failed to extract the jsonLDBody");
        $jsonLDItems = json_decode($rawJson[1], true);

        $this->assertEquals($jsonLDItems["@graph"][1]["headline"], __FUNCTION__);
        $this->assertEquals($jsonLDItems["@graph"][1]["description"], "This is SUPER important for SEO!");
    }
}
