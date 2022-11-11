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
                ],
                "postableDiscussionTypes" => [
                    0 => "discussion",
                    1 => "question",
                    2 => "poll",
                ],
            ],
        ];

        $this->assertHydratesTo($spec, [], $expected);

        //spec with asOwnButtons and excludedButtons params
        $spec = array_merge($spec, ["asOwnButtons" => ["question"], "excludedButtons" => ["poll"]]);
        //adjust expected
        $expected['$reactProps']["asOwnButtons"] = ["question"];
        $expected['$reactProps']["excludedButtons"] = ["poll"];
        $expected['$reactProps']["items"][1]["asOwnButton"] = true;
        unset($expected['$reactProps']["items"][2]);

        $this->assertHydratesTo($spec, [], $expected);
    }
}
