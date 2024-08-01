<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout\Widgets;

use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for quick links.
 */
class QuickLinksWidgetTest extends SiteTestCase
{
    use LayoutTestTrait;

    /**
     * Test hydration and HTML rendering.
     */
    public function testHydrate(): void
    {
        $spec = [
            [
                '$hydrate' => "react.quick-links",
                "title" => "Quick Links",
                "titleType" => "static",
                '$reactTestID' => "defaults",
            ],
            [
                '$hydrate' => "react.quick-links",
                "title" => "My Links",
                "titleType" => "static",
                "links" => [
                    [
                        "name" => "Google",
                        "url" => "https://google.com",
                    ],
                    [
                        "name" => "Vanilla",
                        "url" => "https://vanillaforums.com",
                    ],
                ],
                '$reactTestID' => "specific-links",
            ],
        ];
        $expected = [
            [
                '$reactComponent' => "QuickLinks",
                '$reactProps' => [
                    "title" => "Quick Links",
                    "titleType" => "static",
                    // Default links
                    "links" => [
                        self::markForSparseComparision([
                            "name" => "All Categories",
                            "url" => "/categories",
                        ]),
                        self::markForSparseComparision([
                            "name" => "Recent Discussions",
                            "url" => "/discussions",
                        ]),
                        self::markForSparseComparision([
                            "name" => "Activity",
                            "url" => "/activity",
                        ]),
                        self::markForSparseComparision([
                            "name" => "My Bookmarks",
                            "url" => "/discussions/bookmarked",
                        ]),
                        self::markForSparseComparision([
                            "name" => "My Discussions",
                            "url" => "/discussions/mine",
                        ]),
                        self::markForSparseComparision([
                            "name" => "My Drafts",
                            "url" => "/drafts",
                        ]),
                        self::markForSparseComparision([
                            "name" => "Best Of",
                            "url" => "/bestof",
                        ]),
                    ],
                ],
                '$reactTestID' => "defaults",
                '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Quick Links</h2>
    </div>
    <ul class=linkList>
        <li>
            <a href=/categories>All Categories</a>
        </li>
        <li>
            <a href=/discussions>Recent Discussions</a>
        </li>
        <li>
            <a href=/activity>Activity</a>
        </li>
        <li>
            <a href=/discussions/bookmarked>My Bookmarks</a>
        </li>
        <li>
            <a href=/discussions/mine>My Discussions</a>
        </li>
        <li>
            <a href=/drafts>My Drafts</a>
        </li>
        <li>
            <a href=/bestof>Best Of</a>
        </li>
    </ul>
</div>
HTML
            ,
            ],
            [
                '$reactComponent' => "QuickLinks",
                '$reactProps' => [
                    "title" => "My Links",
                    "titleType" => "static",
                    // Default links
                    "links" => [
                        [
                            "name" => "Google",
                            "url" => "https://google.com",
                        ],
                        [
                            "name" => "Vanilla",
                            "url" => "https://vanillaforums.com",
                        ],
                    ],
                ],
                '$reactTestID' => "specific-links",
            ],
        ];

        $this->assertHydratesTo($spec, [], $expected);
    }
}
