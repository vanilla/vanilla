<?php
/**
 * @copyright 2008-2023 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Forum\Models\PostTypeModel;
use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;
use VanillaTests\UsersAndRolesApiTestTrait;

/**
 * Test New Post Widget.
 */
class NewPostWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;
    use UsersAndRolesApiTestTrait;

    public static $addons = ["qna", "polls", "groups"];

    /**
     * Test that we can hydrate New Post Widget.
     */
    public function testHydrateNewPostWidget()
    {
        $permissionCategory = \CategoryModel::permissionCategory(null);
        $allowedDiscussions = \CategoryModel::getAllowedDiscussionData($permissionCategory, []);

        $spec = [
            '$hydrate' => "react.newpost",
            "title" => "My New Post Button",
            "titleType" => "static",
            '$reactTestID' => "newpost",
        ];

        $expected = [
            '$reactComponent' => "NewPostMenu",
            '$reactProps' => [
                "title" => "My New Post Button",
                "titleType" => "static",
                "asOwnButtons" => [],
                "excludedButtons" => [],
                "items" => [
                    [
                        "label" => $allowedDiscussions["Discussion"]["AddText"],
                        "action" => $allowedDiscussions["Discussion"]["AddUrl"],
                        "type" => "link",
                        "id" => str_replace(" ", "-", strtolower($allowedDiscussions["Discussion"]["AddText"])),
                        "icon" => $allowedDiscussions["Discussion"]["AddIcon"],
                        "asOwnButton" => false,
                    ],
                    [
                        "label" => $allowedDiscussions["Question"]["AddText"],
                        "action" => $allowedDiscussions["Question"]["AddUrl"],
                        "type" => "link",
                        "id" => str_replace(" ", "-", strtolower($allowedDiscussions["Question"]["AddText"])),
                        "icon" => $allowedDiscussions["Question"]["AddIcon"],
                        "asOwnButton" => false,
                    ],
                    [
                        "label" => $allowedDiscussions["Poll"]["AddText"],
                        "action" => $allowedDiscussions["Poll"]["AddUrl"],
                        "type" => "link",
                        "id" => str_replace(" ", "-", strtolower($allowedDiscussions["Poll"]["AddText"])),
                        "icon" => $allowedDiscussions["Poll"]["AddIcon"],
                        "asOwnButton" => false,
                    ],
                    [
                        "label" => $allowedDiscussions["Event"]["AddText"],
                        "action" => $allowedDiscussions["Event"]["AddUrl"],
                        "type" => "link",
                        "id" => str_replace(" ", "-", strtolower($allowedDiscussions["Event"]["AddText"])),
                        "icon" => $allowedDiscussions["Event"]["AddIcon"],
                        "asOwnButton" => false,
                    ],
                ],
                "postableDiscussionTypes" => [
                    0 => "discussion",
                    1 => "question",
                    2 => "poll",
                    3 => "event",
                ],
            ],
            '$reactTestID' => "newpost",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>My New Post Button</h2>
    </div>
    <ul class=linkList>
        <li><a href=/post/discussion>New Discussion</a></li>
        <li><a href=/post/question>Ask a Question</a></li>
        <li><a href=/post/poll>New Poll</a></li>
        <li><a href=/events/new?parentRecordID=-1&amp;parentRecordType=category>New Event</a></li>
    </ul>
</div>
HTML
        ,
        ];

        $this->assertHydratesTo($spec, [], $expected);

        //spec with asOwnButtons and excludedButtons params
        $spec = array_merge($spec, ["asOwnButtons" => ["question"], "excludedButtons" => ["poll"]]);
        //adjust expected
        $expected['$reactProps']["asOwnButtons"] = ["question"];
        $expected['$reactProps']["excludedButtons"] = ["poll"];
        $expected['$reactProps']["items"][1]["asOwnButton"] = true;
        $expected['$seoContent'] = <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>My New Post Button</h2>
    </div>
    <ul class=linkList>
        <li><a href=/post/discussion>New Discussion</a></li>
        <li><a href=/post/question>Ask a Question</a></li>
        <li><a href=/events/new?parentRecordID=-1&amp;parentRecordType=category>New Event</a></li>
    </ul>
</div>
HTML;

        unset($expected['$reactProps']["items"][2]);
        $expected['$reactProps']["items"] = array_values($expected['$reactProps']["items"]);

        $this->assertHydratesTo($spec, [], $expected);
    }

    /**
     * This tests that the links in the New Post Menu contain the category UrlCode
     * if we are on a discussion category page.
     *
     * @return void
     */
    public function testButtonLinksContainCategoryUrlCode()
    {
        $spec = [
            '$hydrate' => "react.newpost",
            '$reactTestID' => "newpost",
        ];

        $expected = self::markForSparseComparision([
            '$reactComponent' => "NewPostMenu",
            '$reactProps' => self::markForSparseComparision([
                "items" => self::markForSparseComparision([
                    self::markForSparseComparision([
                        "action" => "/post/discussion/general",
                    ]),
                    self::markForSparseComparision([
                        "action" => "/post/question/general",
                    ]),
                    self::markForSparseComparision([
                        "action" => "/post/poll/general",
                    ]),
                    self::markForSparseComparision([
                        "action" => "/events/new?parentRecordID=1&parentRecordType=category",
                    ]),
                ]),
            ]),
            '$reactTestID' => "newpost",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <ul class=linkList>
        <li><a href=/post/discussion/general>New Discussion</a></li>
        <li><a href=/post/question/general>Ask a Question</a></li>
        <li><a href=/post/poll/general>New Poll</a></li>
        <li><a href=/events/new?parentRecordID=1&amp;parentRecordType=category>New Event</a></li>
    </ul>
</div>
HTML
        ,
        ]);

        $this->assertHydratesTo($spec, ["categoryID" => 1], $expected, "discussionCategoryPage");
    }

    /**
     * Test hydrating the new post widget with the post type feature.
     * This tests that only post types restricted to a category show up.
     *
     * @return void
     */
    public function testHydrateNewPostWidgetWithPostTypes()
    {
        $this->enableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
        $postType1 = $this->createPostType();
        $postType2 = $this->createPostType();
        $category = $this->createCategory([
            "hasRestrictedPostTypes" => true,
            "allowedPostTypeIDs" => [$postType1["postTypeID"], $postType2["postTypeID"]],
        ]);
        $spec = [
            '$hydrate' => "react.newpost",
            '$reactTestID' => "newpost",
        ];

        $expected = self::markForSparseComparision([
            '$reactComponent' => "NewPostMenu",
            '$reactProps' => self::markForSparseComparision([
                "items" => self::markForSparseComparision([
                    [
                        "label" => $postType1["postButtonLabel"],
                        "action" => "/post/{$postType1["postTypeID"]}/{$category["urlcode"]}",
                        "type" => "link",
                        "id" => str_replace(" ", "-", strtolower($postType1["postButtonLabel"])),
                        "icon" => "new-discussion",
                        "asOwnButton" => false,
                    ],
                    [
                        "label" => $postType2["postButtonLabel"],
                        "action" => "/post/{$postType2["postTypeID"]}/{$category["urlcode"]}",
                        "type" => "link",
                        "id" => str_replace(" ", "-", strtolower($postType2["postButtonLabel"])),
                        "icon" => "new-discussion",
                        "asOwnButton" => false,
                    ],
                ]),
            ]),
            '$reactTestID' => "newpost",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <ul class=linkList>
        <li><a href=/post/{$postType1["postTypeID"]}/{$category["urlcode"]}>{$postType1["postButtonLabel"]}</a></li>
        <li><a href=/post/{$postType2["postTypeID"]}/{$category["urlcode"]}>{$postType2["postButtonLabel"]}</a></li>
    </ul>
</div>
HTML
        ,
        ]);

        $this->assertHydratesTo($spec, ["categoryID" => $category["categoryID"]], $expected, "discussionCategoryPage");
        $this->disableFeature(PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS);
    }
}
