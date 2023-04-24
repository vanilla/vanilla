<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout;

use Garden\Hydrate\DataHydrator;
use PHPUnit\Framework\TestCase;
use Vanilla\Layout\LayoutHydrator;

trait LayoutTestTrait
{
    /**
     * @return LayoutHydrator
     */
    private function getLayoutService(): LayoutHydrator
    {
        return self::container()->get(LayoutHydrator::class);
    }

    /**
     * Assert some layout spec hydrates to an expected value.
     *
     * @param array $hydrateSpec
     * @param array $params
     * @param array|null $expected
     * @param string|null $layoutViewType
     */
    public function assertHydratesTo(
        array $hydrateSpec,
        array $params,
        array $expected = null,
        ?string $layoutViewType = null
    ) {
        $hydrator = $this->getLayoutService()->getHydrator($layoutViewType);
        $actual = $hydrator->resolve($hydrateSpec, $params);
        TestCase::assertEquals($expected, $actual);
    }

    /**
     * Utility for creating a layout section.
     *
     * @param array $content
     * @param array $middleware
     *
     * @return array
     */
    protected function layoutSection(array $content, array $middleware = []): array
    {
        $node = [
            DataHydrator::KEY_HYDRATE => "react.section.1-column",
            "children" => $content,
        ];
        if (!empty($middleware)) {
            $node[DataHydrator::KEY_MIDDLEWARE] = $middleware;
        }
        return $node;
    }

    /**
     * Utility for creating a layout HTML widget.
     *
     * @param string $html
     * @param array $middleware
     *
     * @return string[]
     */
    protected function layoutHtml(string $html, array $middleware = [])
    {
        $node = [
            DataHydrator::KEY_HYDRATE => "react.html",
            "html" => $html,
        ];

        if (!empty($middleware)) {
            $node[DataHydrator::KEY_MIDDLEWARE] = $middleware;
        }
        return $node;
    }

    /**
     * Remove the discussions from a discussionList layout for easier comparison.
     *
     * @param array $layout
     * @return array
     */
    protected function getDiscussionListLayoutMinusDiscussions(array $layout): array
    {
        // Verify the layout was actually hydrated with some discussions.
        $this->assertNotEmpty($layout[0]['$reactProps']["children"][0]['$reactProps']["discussions"]);

        // Now remove them.
        $layout[0]['$reactProps']["children"][0]['$reactProps']["discussions"] = [];
        return $layout;
    }

    /**
     * Get the expected discussionList layout for performing assertions.
     *
     * @return array[]
     */
    protected function getExpectedDiscussionListLayout(): array
    {
        $expected = [
            [
                '$reactComponent' => "SectionOneColumn",
                '$reactProps' => [
                    "children" => [
                        [
                            '$reactComponent' => "DiscussionsWidget",
                            '$reactProps' => [
                                "apiParams" => [
                                    "featuredImage" => false,
                                    "followed" => false,
                                    "categoryID" => null,
                                    "includeChildCategories" => true,
                                    "siteSectionID" => "0",
                                    "sort" => "-dateLastComment",
                                    "limit" => 10,
                                    "pinOrder" => "first",
                                    "expand" => ["all", "-body"],
                                    "excludeHiddenCategories" => true,
                                ],
                                "discussions" => [],
                                "title" => null,
                                "subtitle" => null,
                                "description" => null,
                                "noCheckboxes" => false,
                                "containerOptions" => [],
                                "discussionOptions" => [],
                                "isAsset" => true,
                                "categoryFollowEnabled" => false,
                            ],
                        ],
                    ],
                    "isNarrow" => false,
                ],
            ],
        ];

        return $expected;
    }
}
