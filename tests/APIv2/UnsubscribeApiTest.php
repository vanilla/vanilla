<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use ActivityModel;
use Vanilla\Contracts\ConfigurationInterface;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test the /api/v2/roles endpoints.
 */
class UnsubscribeApiTest extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    /** @var ActivityModel */
    private $activityModel;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = "/unsubscribe";
        $this->activityModel = $this->container()->get(ActivityModel::class);
        $this->config = $this->container()->get(ConfigurationInterface::class);
        $this->config->set(["Feature.UnsubscribeLink.Enabled" => true], null);
    }

    /**
     * Test unsubscribe link and token.
     *
     * @return void
     */
    public function testUnsubscribeLinkToken()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $this->activityModel->save([
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => "DiscussionComment,BookmarkComment"],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DiscussionComment" => "1",
            "Email.BookmarkComment" => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activityUserID, $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        $response = $this->api()->get("{$this->baseUrl}/$token}");
        $body = $response->getBody();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(["Email.DiscussionComment" => true, "Email.BookmarkComment" => true], $body["preferences"]);
        $response = $this->api()->patch("{$this->baseUrl}/$token}", ["preferences" => ["Email.DiscussionComment"]]);

        $this->assertEquals(200, $response->getStatusCode());
        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $discussionComment = val("Email.DiscussionComment", $notifyUser["Preferences"], null);
        $bookmarkComment = val("Email.BookmarkComment", $notifyUser["Preferences"], null);
        $this->assertEquals("0", $discussionComment);
        $this->assertEquals("1", $bookmarkComment);
    }

    /**
     * Test unsubscribe disabled, returns Not Found.
     *
     * @return void
     */
    public function testUnsubscribeNotEnabled()
    {
        $activityUserID = 1;
        $notifyUserID = 2;
        $this->activityModel->save([
            "ActivityUserID" => $activityUserID,
            "Body" => "Hello world.",
            "Format" => "markdown",
            "HeadlineFormat" => __FUNCTION__,
            "Notified" => ActivityModel::SENT_PENDING,
            "NotifyUserID" => $notifyUserID,
            "Data" => ["Reason" => "DiscussionComment,BookmarkComment"],
        ]);

        $notifyUser = $this->userModel->getID($notifyUserID, DATASET_TYPE_ARRAY);
        $this->userModel->savePreference($notifyUser["UserID"], [
            "Email.DiscussionComment" => "1",
            "Email.BookmarkComment" => "1",
        ]);

        $unsubscribeLink = $this->activityModel->getUnsubscribeLink($activityUserID, $notifyUser, "text");

        $link = explode("/unsubscribe/", $unsubscribeLink);
        $token = $link[1];
        $this->runWithConfig(["Feature.UnsubscribeLink.Enabled" => false], function () use ($token) {
            $this->expectExceptionMessage("Page not found.");
            $this->api()->get("{$this->baseUrl}/$token}");
        });
    }
}
