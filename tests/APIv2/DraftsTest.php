<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the /api/v2/drafts endpoints.
 */
class DraftsTest extends AbstractResourceTest
{
    use CommunityApiTestTrait;

    /**
     * {@inheritdoc}
     */
    public function __construct($name = null, array $data = [], $dataName = "")
    {
        $this->baseUrl = "/drafts";
        $this->record = [
            "recordType" => "comment",
            "parentRecordID" => 1,
            "attributes" => [
                "body" => "Hello world. I am a comment.",
                "format" => "Markdown",
            ],
        ];

        $this->patchFields = ["parentRecordID", "attributes"];

        parent::__construct($name, $data, $dataName);
    }

    /**
     * {@inheritdoc}
     */
    protected function modifyRow(array $row)
    {
        $row = parent::modifyRow($row);
        $formats = ["BBCode", "Html", "Markdown", "Text", "TextEx", "Wysiwyg"];
        shuffle($formats);

        if (array_key_exists("parentRecordID", $row)) {
            $row["parentRecordID"]++;
        }
        if (array_key_exists("attributes", $row) && is_array($row["attributes"])) {
            foreach ($row["attributes"] as $key => &$val) {
                if ($key == "format") {
                    $val = $formats[0];
                } elseif (filter_var($val, FILTER_VALIDATE_BOOLEAN)) {
                    $val = !(bool) $val;
                } elseif (filter_var($val, FILTER_VALIDATE_INT)) {
                    $val++;
                } else {
                    $val = strval($val) . microtime();
                }
            }
        }

        return $row;
    }

    /**
     * Verify the ability to create a discussion draft.
     */
    public function testPostDiscussion()
    {
        $data = [
            "recordType" => "discussion",
            "parentRecordID" => 1,
            "attributes" => [
                "announce" => 1,
                "body" => "Hello world.",
                "closed" => 1,
                "format" => "Markdown",
                "name" => "Discussion Draft",
                "sink" => 0,
                "tags" => "interesting,helpful",
            ],
        ];
        parent::testPost($data);
    }

    /**
     * Verify only the author and admins can view a draft (see https://github.com/vanilla/vanilla-patches/issues/726).
     */
    public function testViewingDraftComment()
    {
        $posterID = $this->createUserFixture(self::ROLE_MEMBER);
        $memberID = $this->createUserFixture(self::ROLE_MEMBER);
        $adminID = $this->createUserFixture(self::ROLE_ADMIN);
        $session = $this->getSession();
        $session->start($posterID);
        $record = [
            "recordType" => "comment",
            "parentRecordID" => 1,
            "attributes" => [
                "body" => "I am a comment and in draft form only my author and admins should be able to see me.",
                "format" => "Markdown",
            ],
        ];
        $draftComment = $this->api()
            ->post($this->baseUrl, $record)
            ->getBody();

        // An admin should be able to view the draft.
        $session->start($adminID);
        $viewedDraftData = $this->bessy()
            ->getHtml("post/editcomment?CommentID=&DraftID={$draftComment["draftID"]}")
            ->getInnerHtml();
        $this->assertStringContainsString($record["attributes"]["body"], $viewedDraftData);

        // Another user should get a permission error.
        $session->start($memberID);
        $this->expectExceptionCode(403);
        $this->expectExceptionMessage(t("ErrorPermission"));
        $this->bessy()->get("post/editcomment?CommentID=&DraftID={$draftComment["draftID"]}");
    }

    /**
     * Test that when a draft is saved with a category, the category picker defaults to that category.
     */
    public function testCategoryPickerDefaultsToCategory()
    {
        \Gdn::themeFeatures()->forceFeatures([
            "NewCategoryDropdown" => false,
        ]);
        $this->runWithConfig(
            [
                "Vanilla.Categories.Use" => true,
            ],
            function () {
                $newCat = $this->createCategory();
                $data = [
                    "recordType" => "discussion",
                    "parentRecordID" => $newCat["categoryID"],
                    "attributes" => [
                        "announce" => 0,
                        "body" => "Check the category picker",
                        "closed" => 1,
                        "format" => "Markdown",
                        "name" => "Discussion Draft",
                        "sink" => 0,
                        "tags" => "interesting,helpful",
                    ],
                ];
                $draft = $this->testPost($data);
                $content = $this->bessy()->getHtml("post/editdiscussion/0/{$draft["draftID"]}", [
                    "deliveryType" => DELIVERY_TYPE_ALL,
                ]);

                $content->assertCssSelectorText("option[selected]", $newCat["name"]);
            }
        );
    }

    /**
     * Assert that post made using the API default to the Text format if none is provided.
     */
    public function testDraftEmptyFormat()
    {
        $draft = $this->api()
            ->post($this->baseUrl, [
                "recordType" => "discussion",
                "parentRecordID" => 1,
                "attributes" => [
                    "body" => "Check the category picker",
                ],
            ])
            ->getBody();
        $result = $this->api()
            ->get("$this->baseUrl/{$draft["draftID"]}")
            ->getBody();
        $this->assertEquals($result["attributes"]["format"], "Text");
    }
}
