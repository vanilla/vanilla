<?php
/**
 * @author Gary Pomerant <gpomerant@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\UsersAndRolesApiTestTrait;
use CategoryModel;

/**
 * Test the /api/v2/discussions endpoint returns new comments value.
 */
class NewCommentLabelTestApi extends AbstractAPIv2Test
{
    use UsersAndRolesApiTestTrait;

    /** @var int */
    private static $categoryID;

    /** @var object  */
    private $user1;

    /** @var object  */
    private $user2;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    public static function setupBeforeClass(): void
    {
        parent::setupBeforeClass();

        /** @var CategoryModel $categoryModel */
        $categoryModel = self::container()->get("CategoryModel");
        $category = "Test Category A";
        $urlCode = preg_replace("/[^A-Z0-9]+/i", "-", strtolower($category));
        self::$categoryID = $categoryModel->save([
            "Name" => $category,
            "UrlCode" => $urlCode,
            "InsertUserID" => self::$siteInfo["adminUserID"],
        ]);
    }

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Test new comments count returned in discussion list from API
     */
    public function testNewCommentsCountApi(): void
    {
        $this->user1 = $this->createUser();
        $this->user2 = $this->createUser();
        $categoryID = self::$categoryID;

        $created_discussion = $this->runWithUser(function () use ($categoryID) {
            $discussion = $this->api()
                ->post("/discussions", [
                    "name" => "test discussion",
                    "body" => "Test discussion body",
                    "format" => "text",
                    "categoryID" => $categoryID,
                ])
                ->getBody();
            return $discussion;
        }, $this->user1);

        $discussionID = $created_discussion["discussionID"];

        $this->runWithUser(function () use ($discussionID) {
            return $this->api()
                ->post("/comments", [
                    "body" => "Test Comment",
                    "format" => "text",
                    "discussionID" => $discussionID,
                ])
                ->getBody();
        }, $this->user2);

        $this->runWithUser(function () use ($discussionID) {
            return $this->api()
                ->post("/comments", [
                    "body" => "Test Comment",
                    "format" => "text",
                    "discussionID" => $discussionID,
                ])
                ->getBody();
        }, $this->user2);

        $discussions = $this->runWithUser(function () use ($discussionID) {
            $discussion = $this->api()
                ->get("/discussions")
                ->getBody();
            //testing UI element exists in a generated page
            $discussionPage = $this->bessy()->getHtml("/discussions");
            $discussionPage->assertCssSelectorTextContains("#Discussion_$discussionID", "2 new");
            return $discussion;
        }, $this->user1);

        //unread count returned by API
        foreach ($discussions as $discussion) {
            if ($discussion["discussionID"] == $discussionID) {
                $this->assertEquals(2, $discussion["countUnread"]);
                $passed = true;
            }
        }
        if (empty($passed)) {
            $this->fail("Test Failed. Discussion not found.");
        }
    }
}
