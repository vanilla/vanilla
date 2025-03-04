<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use VanillaTests\SiteTestCase;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Forum\Utils\CommunityApiTestTrait;

/**
 * Test the Site Totals Widget.
 */
class SiteTotalsWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;
    use CommunityApiTestTrait;

    /**
     * Get the names of addons to install to display the default counts
     *
     * @return string[] Returns an array of addon names
     */
    protected static function getAddons(): array
    {
        return ["Online", "QnA"];
    }

    /**
     * Test the we can hydrate the Site Totals Widget
     */
    public function testHydrateSiteTotalsWidget()
    {
        $this->createCategory();
        $this->createDiscussion();
        $this->createDiscussion([
            "type" => "question",
        ]);
        $spec1 = [
            '$hydrate' => "react.sitetotals",
            "apiParams" => [
                "counts" => [
                    [
                        "recordType" => "user",
                        "label" => "Members",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "post",
                        "label" => "Posts",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "onlineUser",
                        "label" => "Online Users",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "discussion",
                        "label" => "Discussions",
                        "isHidden" => true,
                    ],
                    [
                        "recordType" => "comment",
                        "label" => "Comments",
                        "isHidden" => true,
                    ],
                    [
                        "recordType" => "question",
                        "label" => "Questions",
                        "isHidden" => true,
                    ],
                ],
            ],
            '$reactTestID' => "hello",
        ];

        $countsResponse = $this->api->get("/site-totals", ["counts" => ["all"]])->getBody()["counts"];

        $expected1 = [
            '$reactComponent' => "SiteTotalsWidget",
            '$reactProps' => [
                "apiParams" => $spec1["apiParams"],
                "labelType" => "both",
                "formatNumbers" => false,
                "totals" => [
                    [
                        "recordType" => "user",
                        "label" => "Members",
                        "iconName" => "meta-users",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["user"]["count"],
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "post",
                        "label" => "Posts",
                        "iconName" => "meta-posts",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["post"]["count"],
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "onlineUser",
                        "label" => "Online Users",
                        "iconName" => "whos-online",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["onlineUser"]["count"],
                        "isHidden" => false,
                    ],
                ],
            ],
            '$reactTestID' => "hello",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Site Totals</h2>
    </div>
    <div class="gapped row">
        <span class=padded>2 Members</span>
        <span class=padded>2 Posts</span>
        <span class=padded>0 Online Users</span>
    </div>
</div>
HTML
        ,
        ];

        $spec2 = [
            '$hydrate' => "react.sitetotals",
            "apiParams" => [
                "counts" => [
                    [
                        "recordType" => "user",
                        "label" => "Members",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "question",
                        "label" => "Questions",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "post",
                        "label" => "Posts",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "onlineUser",
                        "label" => "Online",
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "comment",
                        "label" => "Comments",
                        "isHidden" => true,
                    ],
                    [
                        "recordType" => "question",
                        "label" => "Questions",
                        "isHidden" => true,
                    ],
                ],
            ],
            "containerOptions" => [
                "alignment" => "space-around",
            ],
            "labelType" => "icon",
            "formatNumbers" => true,
            '$reactTestID' => "hello",
        ];

        $expected2 = [
            '$reactComponent' => "SiteTotalsWidget",
            '$reactProps' => [
                "apiParams" => $spec2["apiParams"],
                "labelType" => "icon",
                "formatNumbers" => true,
                "containerOptions" => [
                    "alignment" => "space-around",
                ],
                "totals" => [
                    [
                        "recordType" => "user",
                        "label" => "Members",
                        "iconName" => "meta-users",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["user"]["count"],
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "question",
                        "label" => "Questions",
                        "iconName" => "meta-questions",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["question"]["count"],
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "post",
                        "label" => "Posts",
                        "iconName" => "meta-posts",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["post"]["count"],
                        "isHidden" => false,
                    ],
                    [
                        "recordType" => "onlineUser",
                        "label" => "Online",
                        "iconName" => "whos-online",
                        "isCalculating" => false,
                        "isFiltered" => false,
                        "count" => $countsResponse["onlineUser"]["count"],
                        "isHidden" => false,
                    ],
                ],
            ],
            '$reactTestID' => "hello",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Site Totals</h2>
    </div>
    <div class="gapped row">
        <span class=padded>2 Members</span>
        <span class=padded>1 Questions</span>
        <span class=padded>2 Posts</span>
        <span class=padded>0 Online</span>
    </div>
</div>
HTML
        ,
        ];

        $this->assertHydratesTo($spec1, [], $expected1);
        $this->assertHydratesTo($spec2, [], $expected2);
    }
}
