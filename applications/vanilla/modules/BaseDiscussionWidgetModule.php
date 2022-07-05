<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\CurrentTimeStamp;
use Vanilla\Exception\PermissionException;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Site\DefaultSiteSection;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\LimitableWidgetInterface;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Class AbstractRecordTypeModule
 *
 * @package Vanilla\Community
 */
class BaseDiscussionWidgetModule extends AbstractReactModule implements LimitableWidgetInterface
{
    use HomeWidgetContainerSchemaTrait, WidgetSchemaTrait;

    /** @var \DiscussionsApiController */
    protected $discussionsApi;

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /** @var \CategoryModel */
    private $categoryModel;

    /** @var \Gdn_Session */
    private $session;

    /** @var \DiscussionsApiController */
    private $api;

    /** @var array Parameters to pass to the API */
    protected $apiParams = [];

    /** @var string */
    protected $title;

    /** @var string */
    protected $subtitle;

    /** @var string */
    protected $description;

    /** @var string */
    protected $viewAllUrl = "/discussions";

    /** @var null|array[] */
    protected $discussions = null;

    /** @var array */
    protected $containerOptions = [];

    /** @var array Other options for discussions*/
    protected $discussionOptions = [];

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApi
     * @param SiteSectionModel $siteSectionModel
     * @param \CategoryModel $categoryModel
     * @param \Gdn_Session $session
     * @param \DiscussionsApiController $api
     */
    public function __construct(
        \DiscussionsApiController $discussionsApi,
        SiteSectionModel $siteSectionModel,
        \CategoryModel $categoryModel,
        \Gdn_Session $session,
        \DiscussionsApiController $api
    ) {
        $this->discussionsApi = $discussionsApi;
        $this->siteSectionModel = $siteSectionModel;
        $this->categoryModel = $categoryModel;
        $this->session = $session;
        $this->api = $api;
        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $apiParams = $this->getRealApiParams();

        $isFollowed = $apiParams["followed"] ?? false;
        if ($isFollowed) {
            if (!$this->session->isValid()) {
                // They couldn't have followed any categories.
                return null;
            }

            $followedCategoryIDs = $this->categoryModel->getFollowed($this->session->UserID);
            if (empty($followedCategoryIDs)) {
                // They didn't follow any categories.
                return null;
            }
        }

        if ($this->discussions === null) {
            try {
                $this->discussions = $this->api->index($apiParams);
            } catch (PermissionException $e) {
                // A user might not have permission to see this.
                return null;
            }
        }

        if ($isFollowed) {
            if (count($this->discussions->getData()) == 0) {
                // They do not have any discussions for their followed categories
                return null;
            }
        }

        $props = [
            "apiParams" => $apiParams,
            "discussions" => $this->discussions,
            "title" => $this->title,
            "subtitle" => $this->subtitle,
            "description" => $this->description,
            "noCheckboxes" => true,
            "containerOptions" => $this->getContainerOptions(),
            "discussionOptions" => $this->getDiscussionOptions(),
        ];

        return $props;
    }

    /**
     * Get react component name
     *
     * @return string
     */
    public static function getComponentName(): string
    {
        return "DiscussionListModule";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema("subtitle"),
            self::widgetDescriptionSchema(),
            Schema::parse([
                "apiParams" => static::getApiSchema(),
            ])
        );

        return $schema;
    }

    /**
     * Get the real parameters that we will pass to the API.
     *
     * @return array
     */
    protected function getRealApiParams(): array
    {
        $apiParams = $this->apiParams;
        $validatedParams = $this->getApiSchema()->validate($apiParams);

        // We want our defaults from the widget schema applied, but to still allow extraneous properties that weren't defined.
        // The widget may be manually configured with API params that are available on the endpoint but not in
        // the widget's form.
        $apiParams = array_merge($apiParams, $validatedParams);

        // Handle the slotType.
        $slotType = $apiParams["slotType"] ?? "";
        $currentTime = CurrentTimeStamp::getDateTime();
        $filterTime = null;
        switch ($slotType) {
            case "w":
                $filterTime = $currentTime->modify("-1 week");
                break;
            case "m":
                $filterTime = $currentTime->modify("-1 month");
                break;
            case "y":
                $filterTime = $currentTime->modify("-1 year");
                break;
            case "a":
            default:
                break;
        }
        // Not a real API parameter.
        unset($apiParams["slotType"]);

        if ($filterTime !== null) {
            // Convert into an API filter.
            $formattedTime = $filterTime->format(\DateTime::RFC3339_EXTENDED);
            $apiParams["dateInserted"] = ">$formattedTime";
        }

        // Force some common expands
        // Default sort.
        $apiParams["sort"] = $apiParams["sort"] ?? "-dateLastComment";
        $apiParams["expand"] = ["all", "-body"];

        // Filter down to the current site section if we haven't set categoryID.
        if (!isset($apiParams["categoryID"])) {
            $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();
            $apiParams["siteSectionID"] = $currentSiteSection->getSectionID();
        }

        // If we enabled to display only categories the user follows, we need to remove the category & subcommunity.
        if ($apiParams["followed"]) {
            $apiParams["siteSectionID"] = null;
            $apiParams["categoryID"] = null;
        }

        return $apiParams;
    }

    /**
     * Get the schema of our api params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        $apiSchema = new Schema([
            "type" => "object",
            "default" => [],
        ]);

        return $apiSchema;
    }

    /**
     * Get only followed categories trigger schema.
     *
     * @return Schema
     */
    protected static function followedCategorySchema(): Schema
    {
        return Schema::parse([
            "followed?" => [
                "type" => "boolean",
                "default" => false,
                "x-control" => SchemaForm::toggle(
                    new FormOptions(
                        t("Display content from followed categories"),
                        t("Enable to only show posts from categories a user follows.")
                    )
                ),
            ],
        ]);
    }

    /**
     * Get categorySchema.
     *
     * @return Schema
     */
    protected static function categorySchema(): Schema
    {
        return Schema::parse([
            "categoryID?" => [
                "type" => ["integer", "null"],
                "default" => null,
                "x-control" => DiscussionsApiIndexSchema::getCategoryIDFormOptions(
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse([
                            "siteSectionID" => [
                                "type" => "null",
                            ],
                            "followed" => [
                                "const" => false,
                            ],
                        ])
                    )
                ),
            ],
            "includeChildCategories?" => [
                "type" => "boolean",
                "default" => true,
                "x-control" => SchemaForm::toggle(
                    new FormOptions(t("Include Child Categories"), t("Include records from child categories.")),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse([
                            "categoryID" => [
                                "type" => "integer",
                            ],
                            "siteSectionID" => [
                                "type" => "null",
                            ],
                            "followed" => [
                                "const" => false,
                            ],
                        ])
                    )
                ),
            ],
        ]);
    }

    /**
     * Get site-section-id schema.
     *
     * @return Schema
     */
    protected static function siteSectionIDSchema(): Schema
    {
        return Schema::parse([
            "siteSectionID?" => [
                "type" => ["string", "null"],
                "default" => null,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions(t("Subcommunity"), t("Display records from this subcommunity.")),
                    new StaticFormChoices(self::getSiteSectionFormChoices()),
                    new FieldMatchConditional(
                        "apiParams",
                        Schema::parse([
                            "categoryID" => [
                                "type" => "null",
                            ],
                            "followed" => [
                                "const" => false,
                            ],
                        ])
                    )
                ),
            ],
        ]);
    }

    /**
     * Get slotType schema.
     *
     * @return Schema
     */
    protected static function getSlotTypeSchema(): Schema
    {
        return Schema::parse([
            "slotType?" => [
                "type" => "string",
                "default" => "a",
                "enum" => ["d", "w", "m", "a"],
                "x-control" => [
                    SchemaForm::radio(
                        new FormOptions(t("Timeframe"), t("Choose when to load records from.")),
                        new StaticFormChoices([
                            "d" => t("Last Day"),
                            "w" => t("Last Week"),
                            "m" => t("Last Month"),
                            "a" => t("All Time"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams.sort",
                            Schema::parse([
                                "type" => "string",
                                "const" => "-score",
                            ])
                        )
                    ),
                    SchemaForm::radio(
                        new FormOptions(t("Timeframe"), t("Choose when to load discussions from.")),
                        new StaticFormChoices([
                            "d" => t("Last Day"),
                            "w" => t("Last Week"),
                            "m" => t("Last Month"),
                            "a" => t("All Time"),
                        ]),
                        new FieldMatchConditional(
                            "apiParams.sort",
                            Schema::parse([
                                "type" => "string",
                                "const" => "-hot",
                            ])
                        )
                    ),
                ],
            ],
        ]);
    }

    /**
     * Get all site-sections form choices.
     *
     * @return array
     */
    protected static function getSiteSectionFormChoices(): array
    {
        /** @var SiteSectionModel $siteSectionModel */
        $siteSectionModel = \Gdn::getContainer()->get(SiteSectionModel::class);
        $siteSections = $siteSectionModel->getAll();

        // If there's only one site-section (default) then we don't
        // need to build the choices.
        if (count($siteSections) === 1) {
            return [];
        }

        $siteSectionFormChoices = [];
        foreach ($siteSections as $siteSection) {
            $id = $siteSection->getSectionID();
            $name = $siteSection->getSectionName();

            if ($id !== (string) DefaultSiteSection::DEFAULT_ID) {
                $siteSectionFormChoices[$id] = $name;
            }
        }

        return $siteSectionFormChoices;
    }

    /**
     * Get a widgets Name.
     *
     * @return string
     */
    public static function getWidgetName(): string
    {
        return "Discussions List";
    }

    ///
    /// Setters
    ///

    /**
     * @param array $apiParams
     */
    public function setApiParams(array $apiParams): void
    {
        $this->apiParams = $apiParams;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitle(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitleContent(string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }

    /**
     * @param string $viewAllUrl
     */
    public function setViewAllUrl(string $viewAllUrl): void
    {
        $this->viewAllUrl = $viewAllUrl;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * Apply discussions that were already fetched from the API.
     *
     * @param array[] $discussions
     */
    public function setDiscussions(array $discussions): void
    {
        $this->discussions = $discussions;
    }

    /**
     * Apply a limit to the number of discussions.
     *
     * @param int $limit
     */
    public function setLimit(int $limit)
    {
        $this->apiParams["limit"] = $limit;
    }

    /**
     * @return array
     */
    public function getContainerOptions(): array
    {
        return $this->containerOptions;
    }

    /**
     * @param array $containerOptions
     */
    public function setContainerOptions(array $containerOptions): void
    {
        $this->containerOptions = $containerOptions;
    }

    /**
     * @return array
     */
    public function getDiscussionOptions(): array
    {
        return $this->discussionOptions;
    }

    /**
     * @param array $discussionOptions
     */
    public function setDiscussionOptions(array $discussionOptions): void
    {
        $this->discussionOptions = $discussionOptions;
    }
}
