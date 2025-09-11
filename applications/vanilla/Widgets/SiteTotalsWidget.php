<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\NoCustomFragmentCondition;
use Vanilla\Http\InternalClient;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Layout\Section\SectionOneColumn;
use Vanilla\Layout\Section\SectionThreeColumns;
use Vanilla\Layout\Section\SectionThreeColumnsEven;
use Vanilla\Layout\Section\SectionTwoColumns;
use Vanilla\Layout\Section\SectionTwoColumnsEven;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\Fragments\SiteTotalsFragmentMeta;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;
use Vanilla\Models\SiteTotalService;
use Vanilla\Site\SiteSectionModel;

/**
 * Widget to display site totals
 */
class SiteTotalsWidget extends ReactWidget
{
    use HomeWidgetContainerSchemaTrait;

    /**
     * DI.
     */
    public function __construct(
        private InternalClient $api,
        private SiteTotalService $siteTotalService,
        private SiteSectionModel $siteSectionModel
    ) {
    }

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [SiteTotalsFragmentMeta::class];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Site Totals";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "sitetotals";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SiteTotalsWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/sitetotals.svg";
    }

    /**
     * Only allow placement in all sections.
     */
    public static function getAllowedSectionIDs(): array
    {
        return [
            SectionOneColumn::getWidgetID(),
            SectionTwoColumns::getWidgetID(),
            SectionThreeColumns::getWidgetID(),
            SectionFullWidth::getWidgetID(),
            SectionThreeColumnsEven::getWidgetID(),
            SectionTwoColumnsEven::getWidgetID(),
        ];
    }

    /**
     * Map icons and labels to record type
     *
     * @param string $recordType
     * @return array
     */
    private static function getRecordDefaults(string $recordType): array
    {
        $recordMap = [
            "accepted" => [
                "label" => "Questions Answered",
                "iconName" => "meta-answered",
            ],
            "article" => [
                "label" => "Articles",
                "iconName" => "meta-article",
            ],
            "category" => [
                "label" => "Categories",
                "iconName" => "meta-categories",
            ],
            "comment" => [
                "label" => "Comments",
                "iconName" => "meta-discussions",
            ],
            "discussion" => [
                "label" => "Discussions",
                "iconName" => "reaction-comments",
            ],
            "event" => [
                "label" => "Events",
                "iconName" => "meta-events",
            ],
            "group" => [
                "label" => "Groups",
                "iconName" => "meta-groups",
            ],
            "knowledgeBase" => [
                "label" => "Knowledge Bases",
                "iconName" => "meta-knowledge-bases",
            ],
            "onlineUser" => [
                "label" => "Online Users",
                "iconName" => "whos-online",
            ],
            "onlineMember" => [
                "label" => "Online Members",
                "iconName" => "meta-users",
            ],
            "post" => [
                "label" => "Posts",
                "iconName" => "meta-posts",
            ],
            "question" => [
                "label" => "Questions",
                "iconName" => "meta-questions",
            ],
            "user" => [
                "label" => "Members",
                "iconName" => "meta-users",
            ],
        ];

        return $recordMap[$recordType];
    }

    /**
     * Get the list of record type options
     *
     * @return array
     */
    public static function getRecordOptions(): array
    {
        $siteTotalService = \Gdn::getContainer()->get(SiteTotalService::class);
        $choices = [];

        $countRecordTypes = $siteTotalService->getCountRecordTypes();
        $countRecordTypes = self::filterUnavailableTypes($countRecordTypes);
        foreach ($countRecordTypes as $recordType) {
            $choices[$recordType] = self::getRecordDefaults($recordType)["label"];
        }

        return $choices;
    }

    /**
     * Get a list of default options for the drag and drop
     *
     * @return array
     */
    private static function getDefaultOptions(): array
    {
        $defaultOptions = ["user", "post", "onlineUser", "discussion", "comment", "question"];
        $siteTotalService = \Gdn::getContainer()->get(SiteTotalService::class);
        $recordTypes = $siteTotalService->getCountRecordTypes();
        $recordTypes = self::filterUnavailableTypes($recordTypes);

        $results = [];

        foreach ($recordTypes as $recordType) {
            $results[] = [
                "recordType" => $recordType,
                "label" => self::getRecordDefaults($recordType)["label"],
                "isHidden" => in_array(haystack: $defaultOptions, needle: $recordType),
            ];
        }

        uasort($results, function (array $a, array $b) {
            if ($a["isHidden"] === $b["isHidden"]) {
                return 0;
            }

            return $a["isHidden"] ? 1 : -1;
        });

        return $results;
    }

    /**
     * API Params
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        $choices = self::getRecordOptions();
        $itemSchema = Schema::parse([
            "recordType:s" => [
                "x-control" => SchemaForm::dropDown(new FormOptions("Type"), new StaticFormChoices($choices)),
            ],
            "label:s" => [
                "x-control" => SchemaForm::textBox(new FormOptions("Label", "Text to display after the count")),
            ],
            "isHidden:b?" => [
                "default" => false,
            ],
        ]);

        $siteSectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);
        $siteSectionSchema = $siteSectionModel->getSiteSectionFormOption(
            new FieldMatchConditional(
                "apiParams.filter",
                Schema::parse([
                    "type" => "boolean",
                    "const" => true,
                ])
            )
        );

        $tSchema = [
            "counts" => [
                "type" => "array",
                "items" => $itemSchema->getSchemaArray(),
                "x-control" => SchemaForm::dragAndDrop(
                    new FormOptions(
                        "Edit Totals",
                        "Select the totals you want to display, rename the label to display after the count, or drag and drop to change the order that the totals should appear."
                    ),
                    $itemSchema
                ),
                "default" => array_values(self::getDefaultOptions()),
            ],
        ];

        if ($siteSectionSchema !== null) {
            $siteFilter = [
                "filter:b?" => [
                    "default" => false,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(
                            "Filter by subcommunity",
                            "Choose if the metrics should be filtered by subcommunity."
                        )
                    ),
                ],
                "siteSectionID?" => $siteSectionSchema,
            ];

            $tSchema = array_merge($tSchema, $siteFilter);
        }

        return Schema::parse($tSchema);
    }

    /**
     * Widget specific options
     *
     * @return Schema
     */
    public static function widgetOptionsSchema(): Schema
    {
        return Schema::parse([
            "labelType:s?" => [
                "default" => "both",
                "enum" => ["both", "icon", "text"],
                "description" => "Select the type of label to display with the count. Icon, text, or both.",
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(
                        "Label Type",
                        "Choose the type of label to display with the count. Icon, text, or both"
                    ),
                    new StaticFormChoices([
                        "both" => "Icon and Text",
                        "icon" => "Icon only",
                        "text" => "Text only",
                    ]),
                    conditions: new NoCustomFragmentCondition(SiteTotalsFragmentMeta::getFragmentType())
                ),
            ],
        ]);
    }

    /**
     * Widget specific options
     *
     * @return Schema
     */
    public static function formatNumbersSchema(): Schema
    {
        return Schema::parse([
            "formatNumbers:b?" => [
                "default" => false,
                "x-control" => SchemaForm::checkBox(
                    new FormOptions(
                        "Format numbers",
                        "Format numbers to be condensed. For example 124k instead of 124,000."
                    )
                ),
            ],
        ]);
    }

    /**
     * Widget specific container options
     *
     * @return Schema
     */
    public static function widgetContainerSchema(): Schema
    {
        return Schema::parse([
            "containerOptions:?" => Schema::parse([
                "background?" => new WidgetBackgroundSchema("Set a full width background for the container.", true),
                "textColor:s?" => [
                    "description" => "Set the color of the text and icons.",
                    "x-control" => SchemaForm::color(
                        new FormOptions(
                            "Text/Icon color",
                            "Select a color for text and/or icons.",
                            "Style Guide Default"
                        )
                    ),
                ],
                "alignment:s?" => [
                    "default" => "center",
                    "enum" => ["flex-start", "center", "flex-end", "space-around"],
                    "description" => "Describe how the counts should be aligned within the container.",
                    "x-control" => SchemaForm::dropDown(
                        new FormOptions("Alignment", "Choose how the counts should be aligned."),
                        new StaticFormChoices([
                            "flex-start" => "Left Aligned",
                            "center" => "Center Aligned",
                            "flex-end" => "Right Aligned",
                            "space-around" => "Justified",
                        ])
                    ),
                ],
            ])
                ->setDescription("Configure various container options")
                ->setField("x-control", SchemaForm::section(new FormOptions("Container Options"))),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            Schema::parse(["apiParams" => self::getApiSchema()]),
            self::widgetOptionsSchema(),
            self::formatNumbersSchema(),
            self::widgetContainerSchema()
        );

        return $schema;
    }

    /**
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        unset($this->props["apiParams"]["options"]);
        $apiParams = $this->props["apiParams"];

        // Filter the totals to only currently available ones.
        $siteTotalService = \Gdn::getContainer()->get(SiteTotalService::class);
        $availableCounts = $siteTotalService->getCountRecordTypes();
        $availableCounts = self::filterUnavailableTypes($availableCounts);

        $filteredCounts = array_filter($apiParams["counts"], function ($item) use ($availableCounts) {
            return !($item["isHidden"] ?? false) && in_array($item["recordType"], $availableCounts);
        });

        // re-index the counts array.
        $filteredCounts = array_values($filteredCounts);

        $counts = array_map(function ($item) {
            return $item["recordType"];
        }, $filteredCounts);

        $urlQuery = ["counts" => $counts];

        if (isset($apiParams["filter"]) && isset($apiParams["siteSectionID"])) {
            $urlQuery["siteSectionID"] = $apiParams["siteSectionID"];
        }

        $countResponse = $this->api->get("/site-totals", $urlQuery)->getBody();

        $totals = [];

        foreach ($filteredCounts as $item) {
            $totals[] = array_merge(
                $countResponse["counts"][$item["recordType"]],
                self::getRecordDefaults($item["recordType"]),
                $item
            );
        }

        $this->props["totals"] = $totals;
        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $result = "";
        foreach ($props["totals"] as $total) {
            if ($total["count"] < 0) {
                continue;
            }
            $result .= "<span class='padded'>{$total["count"]} {$total["label"]}</span>";
        }
        $result = $this->renderWidgetContainerSeoContent(
            [
                "title" => t("Site Totals"),
            ],
            "<div class='row gapped'>{$result}</div>"
        );
        return $result;
    }

    /**
     * This is a temporary kludge to filter out the customPage record type.
     * It is currently only registered with SiteTotalService for compatibility with Elastic.
     *
     * @param array $recordTypes
     * @return array
     */
    private static function filterUnavailableTypes(array $recordTypes): array
    {
        return array_filter($recordTypes, fn($recordType) => $recordType !== "customPage");
    }
}
