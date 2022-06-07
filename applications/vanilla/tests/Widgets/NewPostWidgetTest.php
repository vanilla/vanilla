<?php
/**
 * @copyright 2008-2022 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test New Post Widget.
 */
class NewPostWidgetTest extends SiteTestCase
{
    use LayoutTestTrait, CommunityApiTestTrait;

    /**
     * Get the names of addons to install.
     *
     * @return string[] Returns an array of addon names.
     */
    protected static function getAddons(): array
    {
        return ["vanilla", "qna", "polls"];
    }

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
        ];

        $expected = [
            '$reactComponent' => "NewPostMenu",
            '$reactProps' => [
                "title" => "My New Post Button",
                "asOwnButtons" => [],
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
                ],
            ],
        ];

        $this->assertHydratesTo($spec, [], $expected);

        //spec with asOwnButtons param
        $spec = array_merge($spec, ["asOwnButtons" => ["question"]]);
        //adjust expected
        $expected['$reactProps']["asOwnButtons"] = ["question"];
        $expected['$reactProps']["items"][1]["asOwnButton"] = true;

        $this->assertHydratesTo($spec, [], $expected);
    }
}
