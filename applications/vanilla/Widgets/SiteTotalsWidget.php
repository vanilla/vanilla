<?php
/**
 * @author Jenny Seburn <jseburn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Http\InternalClient;
use Vanilla\InjectableInterface;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Layout\Section\SectionFullWidth;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\Schema\WidgetBackgroundSchema;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;
use Vanilla\Models\SiteTotalService;
use Vanilla\Site\SiteSectionModel;

/**
 * Widget to display site totals
 */
class SiteTotalsWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, InjectableInterface
{
    use HomeWidgetContainerSchemaTrait;
    use CombinedPropsWidgetTrait;
    use DefaultSectionTrait;

    /** @var InternalClient */
    private $api;

    /** @var SiteTotalService */
    private $siteTotalService;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * DI.
     *
     * @param InternalClient $api
     * @param SiteTotalService $siteTotalService
     * @param SiteSectionModel $siteSectionModel
     */
    public function setDependencies(
        InternalClient $api,
        SiteTotalService $siteTotalService,
        SiteSectionModel $siteSectionModel
    ) {
        $this->api = $api;
        $this->siteTotalService = $siteTotalService;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "Site Totals";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string
    {
        return "sitetotals";
    }

    /**
     * @inheritDoc
     */
    public static function getComponentName(): string
    {
        return "SiteTotalsWidget";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/sitetotals.svg";
    }

    /**
     * Only allow placement in a full width section
     */
    public static function getAllowedSectionIDs(): array
    {
        return [SectionFullWidth::getWidgetID()];
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
                "iconName" => "search-answered",
            ],
            "article" => [
                "label" => "Articles",
                "iconName" => "data-article",
            ],
            "category" => [
                "label" => "Categories",
                "iconName" => "search-categories",
            ],
            "comment" => [
                "label" => "Comments",
                "iconName" => "search-discussion",
            ],
            "discussion" => [
                "label" => "Discussions",
                "iconName" => "reaction-comments",
            ],
            "event" => [
                "label" => "Events",
                "iconName" => "search-events",
            ],
            "group" => [
                "label" => "Groups",
                "iconName" => "search-groups",
            ],
            "knowledgeBase" => [
                "label" => "Knowledge Bases",
                "iconName" => "search-kb",
            ],
            "onlineUser" => [
                "label" => "Online Users",
                "iconName" => "data-online",
            ],
            "onlineMember" => [
                "label" => "Online Members",
                "iconName" => "search-members",
            ],
            "post" => [
                "label" => "Posts",
                "iconName" => "search-post-count",
            ],
            "question" => [
                "label" => "Questions",
                "iconName" => "search-questions",
            ],
            "user" => [
                "label" => "Members",
                "iconName" => "search-members",
            ],
        ];

        return $recordMap[$recordType];
    }

    /**
     * Get the list of record type options
     *
     * @return array
     */
    private static function getRecordOptions(): array
    {
        $siteTotalService = \Gdn::getContainer()->get(SiteTotalService::class);
        $choices = [];

        foreach ($siteTotalService->getCountRecordTypes() as $recordType) {
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

        $options = array_filter($defaultOptions, function ($recordType) use ($recordTypes) {
            return in_array($recordType, $recordTypes);
        });

        if (count($options) < count($recordTypes) && count($options) < count($defaultOptions)) {
            $extraCount = count($defaultOptions) - count($options);
            $otherRecords = array_filter($recordTypes, function ($recordType) use ($defaultOptions) {
                return !in_array($recordType, $defaultOptions);
            });
            $options = array_merge($options, array_slice($otherRecords, 0, $extraCount));
        }

        $defaultShow = array_map(function ($recordType) {
            return [
                "recordType" => $recordType,
                "label" => self::getRecordDefaults($recordType)["label"],
            ];
        }, array_slice($options, 0, 3));

        $defaultHide = array_map(function ($recordType) {
            return [
                "recordType" => $recordType,
                "label" => self::getRecordDefaults($recordType)["label"],
                "isHidden" => true,
            ];
        }, array_slice($options, 3, 3));

        return array_merge($defaultShow, $defaultHide);
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
            "isHidden:b?",
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
            "options?" => [
                "type" => "object",
                "default" => $choices,
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
                    ])
                ),
            ],
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
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            Schema::parse(["apiParams" => self::getApiSchema()]),
            self::widgetOptionsSchema(),
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

        $filteredCounts = array_filter($apiParams["counts"], function ($item) use ($availableCounts) {
            return !$item["isHidden"] && in_array($item["recordType"], $availableCounts);
        });

        $counts = array_map(function ($item) {
            return $item["recordType"];
        }, $filteredCounts);

        $urlQuery = ["counts" => $counts];

        if (isset($apiParams["filter"]) && isset($apiParams["siteSectionID"])) {
            $urlQuery["siteSectionID"] = [$apiParams["siteSectionID"]];
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
}
