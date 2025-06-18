<?php
/**
 * @author Maneesh Chiba <maneesh.chiba@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Forum\Widgets;

use Vanilla\Forum\Widgets\SearchWidget;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Layout\LayoutTestTrait;
use VanillaTests\Storybook\StorybookGenerationTestCase;

/**
 * Tests covering the SearchWidget.
 */
class SearchWidgetStorybookTest extends StorybookGenerationTestCase
{
    use LayoutTestTrait;
    use EventSpyTestTrait;

    public static $addons = ["vanilla"];

    /** @var string */
    private $placeholderStub = "Search this community...";

    /**
     * Event handler to mount Search widget.
     *
     * @param \Gdn_Controller $sender
     */
    public function base_render_before(\Gdn_Controller $sender)
    {
        /** @var SearchWidget $widget */
        $widget = new SearchWidget(\Gdn::addonManager());
        $props = [
            "placeholder" => $this->placeholderStub,
            "domain" => "all",
            "borderRadius" => "6px",
        ];

        $widget->setProps($props);
    }

    public function testRender()
    {
        $this->generateStoryHtml("/", "Search Widget");
    }

    /**
     * Test hydrating the SearchWidget.
     */
    public function testHydrateSearchWidget()
    {
        $spec = [
            '$hydrate' => "react.community-search",
            "title" => "Search Our Community",
            "titleType" => "static",
            "descriptionType" => "static",
            "description" => "Find answers to your questions",
            "placeholder" => $this->placeholderStub,
            "domain" => "all",
            "borderRadius" => "6px",
            '$reactTestID' => "searchwidget",
        ];
        $expected = [
            '$reactComponent' => "SearchWidget",
            '$reactProps' => [
                "title" => "Search Our Community",
                "titleType" => "static",
                "descriptionType" => "static",
                "description" => "Find answers to your questions",
                "placeholder" => $this->placeholderStub,
                "domain" => "all",
                "borderRadius" => "6px",
            ],
            '$reactTestID' => "searchwidget",
            '$seoContent' => <<<HTML
<div class=pageBox>
    <div class=pageHeadingBox>
        <h2>Search Our Community</h2>
        <p>Find answers to your questions</p>
    </div>
        You need to Enable Javascript to search this community.
</div>
HTML
        ,
        ];

        $this->assertHydratesTo($spec, [], $expected);
    }
}
