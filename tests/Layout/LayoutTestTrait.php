<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Layout;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\Hydrate\DataHydrator;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Vanilla\ApiUtils;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutService;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\DebugUtils;
use Vanilla\Utility\StringUtils;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\VanillaTestCase;

trait LayoutTestTrait
{
    use HtmlNormalizeTrait;

    /**
     * @return LayoutHydrator
     * @throws ContainerException
     * @throws NotFoundException
     */
    protected function getLayoutHydrator(): LayoutHydrator
    {
        return \Gdn::getContainer()->get(LayoutHydrator::class);
    }

    /**
     * @return LayoutService
     */
    protected function getLayoutService(): LayoutService
    {
        return \Gdn::getContainer()->get(LayoutService::class);
    }

    /**
     * Assert some layout spec hydrates to an expected value.
     *
     * @param array $hydrateSpec
     * @param array $params
     * @param array|null $expected
     * @param string|null $layoutViewType
     *
     * @return array The hydrated layout.
     */
    public function assertHydratesTo(
        array $hydrateSpec,
        array $params,
        ?array $expected,
        ?string $layoutViewType = null
    ): array {
        // Serialize back and forth to see things the way the API sees it.

        $this->validateTestIDs($hydrateSpec, "Hydrate Spec assertion error");
        $this->validateTestIDs($expected, "Hydrate expection error");
        $actual = $this->getLayoutHydrator()->hydrateLayout($layoutViewType, $params, $hydrateSpec, false);

        $expectedSeoContentByTestID = $this->extractFieldByTestID($expected, '$seoContent');
        $actualSeoContentByTestID = $this->extractFieldByTestID($actual, '$seoContent');
        $actualReactPropsByTestID = $this->extractFieldByTestID($actual, '$reactProps');

        // Strip off seoHtml. We'll assert against these separately.
        $expected = $this->getLayoutService()->stripSeoHtmlFromHydratedLayout($expected);
        // See things as an API client sees things.
        $expected = ApiUtils::jsonFilter($expected);
        $expected = ArrayUtils::jsonNormalizeArray($expected);

        $actual = $this->getLayoutService()->stripSeoHtmlFromHydratedLayout($actual);
        $actual = ApiUtils::jsonFilter($actual);
        $actual = ArrayUtils::jsonNormalizeArray($actual);

        VanillaTestCase::assertEqualsSparse($expected, $actual);

        // Compare the expected HTML
        foreach ($expectedSeoContentByTestID as $testID => $expectedSeoContent) {
            if ($expectedSeoContent === null) {
                // Only test if the assertion was made.
                continue;
            }
            $actualSeoContent = $actualSeoContentByTestID[$testID] ?? "";
            $actualProps = $actualReactPropsByTestID[$testID] ?? new \stdClass();
            $this->assertHtmlStringEqualsHtmlString(
                $expectedSeoContent,
                $actualSeoContent,
                "Incorrect SEO HTML for react component {$testID} with props\n" .
                    DebugUtils::jsonEncodeSummary($actualProps, JSON_PRETTY_PRINT)
            );
        }
        return $actual;
    }

    /**
     * Recusively extract pairs of $reactTestID => $extractionField from a layout.
     *
     * @param array|null $spec A layout spec (hydrated or unhydrated)
     * @param array $extractionField
     *
     * @return array<string, string>
     */
    private function extractFieldByTestID(?array $spec, string $extractionField): array
    {
        $result = [];
        if ($spec === null) {
            return $result;
        }

        ArrayUtils::walkRecursiveArray($spec, function ($node) use (&$result, $extractionField) {
            $extractedField = $node[$extractionField] ?? null;
            $reactTestID = $node['$reactTestID'] ?? null;
            if ($reactTestID !== null) {
                $result[$reactTestID] = $extractedField;
            }
        });
        return $result;
    }

    /**
     * Ensure that all $seoContent have an expected $reactTestID.
     *
     * @param array|null $hydrateSpec
     * @param string $errorMessage
     *
     * @return void
     */
    private function validateTestIDs(?array &$hydrateSpec, string $errorMessage)
    {
        if ($hydrateSpec === null) {
            return;
        }
        ArrayUtils::walkRecursiveArray($hydrateSpec, function (&$node, $path) use ($errorMessage) {
            $pathStr = implode("/", $path);
            $testID = $node['$reactTestID'] ?? null;
            $seoContent = $node['$seoContent'] ?? null;

            if ($seoContent !== null && $testID === null) {
                TestCase::fail(
                    implode("\n", [
                        $errorMessage,
                        "React widget at path '$pathStr' requires a '\$reactTestID' because it is trying to assert '\$seoContent'.\n" .
                        DebugUtils::jsonEncodeSummary($node, JSON_PRETTY_PRINT),
                    ])
                );
            }
        });
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
     * Remove the discussions from a discussionList layout for easier comparison.
     *
     * @param array $layout
     * @return array
     */
    protected function getCategoryListLayoutMinusCategories(array $layout): array
    {
        // Verify the layout was actually hydrated with some discussions.
        $this->assertNotEmpty($layout[0]['$reactProps']["children"][0]['$reactProps']["itemData"]);

        // Now remove them.
        $layout[0]['$reactProps']["children"][0]['$reactProps']["itemData"] = [];
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
                                    "sort" => "-dateLastComment",
                                    "slotType" => "a",
                                    "limit" => 10,
                                    "siteSectionID" => "0",
                                    "pinOrder" => "first",
                                    "page" => 1,
                                    "expand" => ["all", "-body"],
                                    "excludeHiddenCategories" => true,
                                ],
                                "discussions" => [],
                                "initialPaging" => [
                                    "nextURL" => null,
                                    "prevURL" => null,
                                    "currentPage" => 1,
                                    "total" => 4,
                                    "limit" => 10,
                                ],
                                "title" => null,
                                "subtitle" => null,
                                "description" => null,
                                "noCheckboxes" => false,
                                "containerOptions" => [],
                                "discussionOptions" => [],
                                "isAsset" => true,
                                "defaultSort" => "-dateLastComment",
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

    /**
     * Get the expected categoryList layout for performing assertions.
     *
     * @return array[]
     */
    protected function getExpectedCategoryListLayout()
    {
        $expected = [
            [
                '$reactComponent' => "SectionOneColumn",
                '$reactProps' => [
                    "children" => [
                        [
                            '$reactComponent' => "CategoriesWidget",
                            '$reactProps' => [
                                "titleType" => "none",
                                "descriptionType" => "none",
                                "apiParams" => [
                                    "sort" => "-dateLastComment",
                                ],
                                "itemData" => [],
                                "isAsset" => true,
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
