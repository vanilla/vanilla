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
 * Tests for the various layout sections.
 */
class SectionHydrationTest extends SiteTestCase
{
    use LayoutTestTrait;

    /**
     * Test the full width and single column sections.
     */
    public function testFullWidthAndOneColumn()
    {
        $widget1 = $this->dummyWidget("widget1");
        $widget2 = $this->dummyWidget("widget2");
        $expectedHtml = <<<HTML
<section>
    <div class=seoSectionPiece>
        <div class=sectionItem>
            <dummy>widget1</dummy>
        </div>
        <div class=sectionItem>
            <dummy>widget2</dummy>
        </div>
    </div>
</section>
HTML;

        $spec = [
            [
                '$hydrate' => "react.section.1-column",
                "children" => [$widget1, $widget2],
                '$reactTestID' => "section1",
            ],
            [
                '$hydrate' => "react.section.full-width",
                "children" => [$widget1, $widget2],
                '$reactTestID' => "sectionFull",
            ],
        ];

        $expected = [
            [
                '$reactComponent' => "SectionOneColumn",
                '$reactProps' => [
                    "children" => [$widget1, $widget2],
                    "isNarrow" => false,
                ],
                '$reactTestID' => "section1",
                '$seoContent' => $expectedHtml,
            ],
            [
                '$reactComponent' => "SectionFullWidth",
                '$reactProps' => [
                    "children" => [$widget1, $widget2],
                ],
                '$reactTestID' => "sectionFull",
                '$seoContent' => $expectedHtml,
            ],
        ];
        $this->assertHydratesTo($spec, [], $expected);
    }

    /**
     * Test the 2 column section.
     */
    public function testSectionTwoColumns()
    {
        $dummy1 = $this->dummyWidget("dummy1");
        $dummy2 = $this->dummyWidget("dummy2");
        $dummy3 = $this->dummyWidget("dummy3");
        $dummy4 = $this->dummyWidget("dummy4");
        $breadcrumb = $this->dummyWidget("breadcrumbWidget");

        $spec = [
            '$hydrate' => "react.section.2-columns",
            "mainTop" => [$dummy1, null, $dummy2],
            "mainBottom" => [$dummy2, $dummy3],
            "secondaryTop" => [null], // Empty
            "secondaryBottom" => [$dummy3, $dummy4],
            '$reactTestID' => "section",
            "breadcrumbs" => [$breadcrumb],
        ];

        $expected = [
            '$reactComponent' => "SectionTwoColumns",
            '$reactProps' => [
                "mainTop" => [$dummy1, $dummy2],
                "mainBottom" => [$dummy2, $dummy3],
                "secondaryTop" => [], // Null values are stripped
                "secondaryBottom" => [$dummy3, $dummy4],
                "isInverted" => false,
                "breadcrumbs" => [$breadcrumb],
            ],
            '$reactTestID' => "section",
            '$seoContent' => <<<HTML
<section>
    <div class="seoSectionRow seoBreadcrumbs">
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>breadcrumbWidget</dummy>
            </div>
        </div>
    </div>
    <div class="mainColumn seoSectionColumn">
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy1</dummy>
            </div>
            <div class=sectionItem>
                <dummy>dummy2</dummy>
            </div>
        </div>
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy2</dummy>
            </div>
            <div class=sectionItem>
                <dummy>dummy3</dummy>
            </div>
        </div>
    </div>
    <div class=seoSectionColumn>
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy3</dummy>
            </div>
            <div class=sectionItem>
                <dummy>dummy4</dummy>
            </div>
        </div>
    </div>
</section>
HTML
        ,
        ];
        $this->assertHydratesTo($spec, [], $expected);
    }

    /**
     * Test the three column section.
     */
    public function testSectionThreeColumns()
    {
        $dummy1 = $this->dummyWidget("dummy1");
        $dummy2 = $this->dummyWidget("dummy2");
        $dummy3 = $this->dummyWidget("dummy3");
        $dummy4 = $this->dummyWidget("dummy4");
        $dummy5 = $this->dummyWidget("dummy5");
        $breadcrumb = $this->dummyWidget("breadcrumbWidget");

        $spec = [
            '$hydrate' => "react.section.3-columns",
            "leftTop" => [$dummy5],
            "leftBottom" => [null],
            "middleTop" => [$dummy1, null, $dummy2],
            "middleBottom" => [$dummy2, $dummy3],
            "rightTop" => [null], // Empty
            "rightBottom" => [$dummy3, $dummy4],
            '$reactTestID' => "section",
            "breadcrumbs" => [$breadcrumb],
        ];

        $expected = [
            '$reactComponent' => "SectionThreeColumns",
            '$reactProps' => [
                "leftTop" => [$dummy5],
                "leftBottom" => [],
                "middleTop" => [$dummy1, $dummy2],
                "middleBottom" => [$dummy2, $dummy3],
                "rightTop" => [], // Null values are stripped
                "rightBottom" => [$dummy3, $dummy4],
                "breadcrumbs" => [$breadcrumb],
            ],
            '$reactTestID' => "section",
            '$seoContent' => <<<HTML
<section>
    <div class="seoSectionRow seoBreadcrumbs">
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>breadcrumbWidget</dummy>
            </div>
        </div>
    </div>
    <div class=seoSectionColumn>
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy5</dummy>
            </div>
        </div>
    </div>
    <div class="mainColumn seoSectionColumn">
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy1</dummy>
            </div>
            <div class=sectionItem>
                <dummy>dummy2</dummy>
            </div>
        </div>
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy2</dummy>
            </div>
            <div class=sectionItem>
                <dummy>dummy3</dummy>
            </div>
        </div>
    </div>
    <div class=seoSectionColumn>
        <div class=seoSectionPiece>
            <div class=sectionItem>
                <dummy>dummy3</dummy>
            </div>
            <div class=sectionItem>
                <dummy>dummy4</dummy>
            </div>
        </div>
    </div>
</section>
HTML
        ,
        ];
        $this->assertHydratesTo($spec, [], $expected);
    }

    /**
     * Create an already hydrated dummy widget.
     */
    private function dummyWidget(string $name)
    {
        return [
            '$reactComponent' => "react.dummy",
            '$reactProps' => [
                "name" => $name,
            ],
            '$reactTestID' => "dummy-{$name}-" . randomString(32),
            '$seoContent' => "<dummy>$name</dummy>",
        ];
    }
}
