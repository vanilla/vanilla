<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community;

use CategoryModel;
use Exception;
use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use Gdn_Session;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forum\Widgets\DiscussionsWidgetSchemaTrait;
use Vanilla\Http\InternalClient;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\LegacyReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\LimitableWidgetInterface;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Class AbstractRecordTypeModule
 *
 * @package Vanilla\Community
 */
class BaseDiscussionWidgetModule extends LegacyReactModule implements LimitableWidgetInterface, HydrateAwareInterface
{
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use DiscussionsWidgetSchemaTrait;
    use HydrateAwareTrait;

    protected InternalClient $api;

    /** @var SiteSectionModel */
    public SiteSectionModel $siteSectionModel;

    /** @var CategoryModel */
    public CategoryModel $categoryModel;

    /** @var Gdn_Session */
    private Gdn_Session $session;

    /** @var array Parameters to pass to the API */
    protected $apiParams = [];

    /** @var string */
    protected $title;

    /** @var string */
    protected $subtitle;

    /** @var string */
    protected $description;

    /** @var null|string */
    protected ?string $siteSectionID = null;

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
     * @param SiteSectionModel $siteSectionModel
     * @param CategoryModel $categoryModel
     * @param Gdn_Session $session
     */
    public function __construct(
        InternalClient $api,
        SiteSectionModel $siteSectionModel,
        CategoryModel $categoryModel,
        Gdn_Session $session
    ) {
        $this->api = $api;
        $this->siteSectionModel = $siteSectionModel;
        $this->categoryModel = $categoryModel;
        $this->session = $session;
        $this->addChildComponentName("DiscussionListModule");
        parent::__construct();
    }

    /**
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     * @throws ValidationException
     * @throws HttpException
     * @throws NotFoundException
     */
    public function getProps(?array $params = null): ?array
    {
        $isAsset = $params["isAsset"] ?? false;
        $apiParams = $this->getRealApiParams($params["apiParams"] ?? null);

        $isFollowed = $apiParams["followed"] ?? false;
        if ($isFollowed) {
            if (!$this->session->isValid()) {
                // They couldn't have followed any categories.
                return null;
            }

            $followedCategoryIDs = $this->categoryModel->getFollowed($this->session->UserID);
            if (empty($followedCategoryIDs) && !$isAsset) {
                // They didn't follow any categories.
                return null;
            }
        }

        if ($this->discussions === null) {
            try {
                $this->discussions = $this->api->get("/discussions", $apiParams)->asData();
            } catch (Exception $e) {
                if (!$e->getMessage() == "Permission Problem") {
                    throw $e;
                }
                // A user might not have permission to see this.
                return null;
            }
        }

        if ($isFollowed) {
            if (count($this->discussions->getData()) == 0 && !$isAsset) {
                // They do not have any discussions for their followed categories
                return null;
            }
        }

        $props = [
            "apiParams" => $apiParams,
            "discussions" => $this->discussions,
            "initialPaging" => $this->discussions->getPaging(),
            "title" => $params["title"] ?? $this->title,
            "subtitle" => $params["subtitle"] ?? $this->subtitle,
            "description" => $params["description"] ?? $this->description,
            "noCheckboxes" => $params["noCheckboxes"] ?? true,
            "containerOptions" => $params["containerOptions"] ?? $this->getContainerOptions(),
            "discussionOptions" => $params["discussionOptions"] ?? $this->getDiscussionOptions(),
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
     * @param array|null $params
     * @return array
     * @throws ValidationException
     */
    protected function getRealApiParams(?array $params = null): array
    {
        $apiParams = $params ?? $this->apiParams;
        $validatedParams = $this->getApiSchema()->validate($apiParams);
        // We want our defaults from the widget schema applied, but to still allow extraneous properties that weren't defined.
        // The widget may be manually configured with API params that are available on the endpoint but not in
        // the widget's form.
        $apiParams = array_merge($apiParams, $validatedParams);

        // Force some common expands
        // Default sort.
        $apiParams["sort"] = $apiParams["sort"] ?? "-dateLastComment";
        $apiParams["expand"] = ["all", "-body"];

        // Filter down to the current site section if we haven't set categoryID.
        if (!isset($apiParams["categoryID"]) && !isset($apiParams["siteSectionID"])) {
            $currentSiteSection = $this->siteSectionModel->getCurrentSiteSection();
            $apiParams["siteSectionID"] = $currentSiteSection->getSectionID();
        }

        // If we enabled to display only categories the user follows, we need to remove the category.
        if ($apiParams["followed"] ?? false) {
            $apiParams["categoryID"] = null;
        }

        if (!key_exists("excludeHiddenCategories", $apiParams)) {
            // Hide discussions from hidden categories
            $apiParams["excludeHiddenCategories"] = true;
        }
        return $apiParams;
    }

    /**
     * Get the real parameters that we will pass to the API for widgets.
     * @param array|null $params
     * @return array
     * @throws ValidationException
     */
    protected function getWidgetRealApiParams(?array $params = null): array
    {
        $apiParams = $params ?? $this->apiParams;
        $validatedParams = $this->getApiSchema()->validate($apiParams);

        // We want our defaults from the widget schema applied, but to still allow extraneous properties that weren't defined.
        // The widget may be manually configured with API params that are available on the endpoint but not in
        // the widget's form.
        $apiParams = array_merge($apiParams, $validatedParams);

        $explicitNoneFilter = ($apiParams["filter"] ?? "notNone") == "none";
        switch ($apiParams["filter"] ?? "none") {
            // Filtering by subcommunity
            case "subcommunity":
                // Unset apiParams unrelated to subcommuny filtering.
                unset($apiParams["filterCategorySubType"]);
                unset($apiParams["categoryID"]);

                if ($apiParams["filterSubcommunitySubType"] == "contextual") {
                    $apiParams["siteSectionID"] = $this->siteSectionModel->getCurrentSiteSection()->getSectionID();
                }
                break;
            // Filtering by category
            case "category":
                // Unset apiParams unrelated to category filtering.
                unset($apiParams["filterSubcommunitySubType"]);
                unset($apiParams["siteSectionID"]);

                // If we are trying to filter contextually by category
                if ($apiParams["filterCategorySubType"] == "contextual") {
                    $apiParams["categoryID"] =
                        $this->getHydrateParam("category.categoryID") ?? $this->categoryModel::ROOT_ID;
                }
                break;
            // Filtering by no specific parameter
            case "none":
                if ($explicitNoneFilter) {
                    unset($apiParams["categoryID"]);
                    unset($apiParams["siteSectionID"]);
                }
                // Remove any restrictive filtering options.
                unset($apiParams["filterSubcommunitySubType"]);
                unset($apiParams["filterCategorySubType"]);
                break;
        }

        // Use possible siteSectionID from overwrite.
        if ($this->siteSectionID !== null) {
            $apiParams["siteSectionID"] = $this->siteSectionID;
        }

        // Force some common expands & default sort.
        $apiParams["sort"] = $apiParams["sort"] ?? "-dateLastComment";
        $apiParams["expand"] = ["all", "-body"];

        if (!key_exists("excludeHiddenCategories", $apiParams)) {
            // Hide discussions from hidden categories
            $apiParams["excludeHiddenCategories"] = true;
        }

        return $apiParams;
    }

    /**
     * Get the schema of our api params.
     *
     * @return Schema
     */
    public static function getBaseApiSchema(): Schema
    {
        $apiSchema = new Schema([
            "type" => "object",
            "default" => new \stdClass(),
            "properties" => [
                "featuredImage" => [
                    "type" => "boolean",
                    "default" => false,
                    "x-control" => SchemaForm::toggle(
                        new FormOptions(
                            "Featured Image",
                            "Show a featured image when available.",
                            "",
                            "Post will show a featured image when available. If there's nothing to show, the branded default image will show."
                        )
                    ),
                ],
                "fallbackImage" => [
                    "type" => "string",
                    "description" =>
                        "By default, an SVG image using your brand color displays when there's nothing else to show. Upload your own image to customize. Recommended size: 1200px by 600px.",
                    "x-control" => SchemaForm::upload(
                        new FormOptions(
                            "Fallback Image",
                            "Upload your own image to override the default SVG.",
                            "Choose Image",
                            "By default, an SVG image using your brand color displays when there's nothing else to show. Upload your own image to customize. Recommended size: 1200px by 600px."
                        ),
                        new FieldMatchConditional(
                            "apiParams.featuredImage",
                            Schema::parse([
                                "type" => "boolean",
                                "const" => true,
                            ])
                        )
                    ),
                ],
            ],
            "required" => [],
        ]);

        return $apiSchema
            ->setDescription("Configure API options")
            ->setField("x-control", SchemaForm::section(new FormOptions("Display Options")));
    }

    /**
     * Get the schema of our api params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema
    {
        return self::getBaseApiSchema();
    }

    /**
     * Get a widgets Name.
     *
     * @return string
     */
    public static function getWidgetName(): string
    {
        return "Post List";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Community";
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

    public function setSiteSectionID(string $siteSectionID)
    {
        $this->siteSectionID = $siteSectionID;
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

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($props["discussions"] ?? []));
        return $result;
    }

    /**
     * Return filterTypeSchemaExtraOptions depending on the current `layoutViewType`.
     *
     * @return array|false[]
     */
    public static function getFilterTypeSchemaExtraOptions(): array
    {
        // We may have a provided `layoutViewType`, or not.
        $layoutViewType = Gdn::request()->get("layoutViewType", false);
        switch ($layoutViewType) {
            case "home":
                $filterTypeSchemaExtraOptions = [
                    "hasSubcommunitySubTypeOptions" => false,
                    "hasCategorySubTypeOptions" => false,
                ];
                break;
            case "subcommunityHome":
            case "discussionList":
                $filterTypeSchemaExtraOptions = [
                    "hasCategorySubTypeOptions" => false,
                ];
                break;
            case "categoryList":
            case "discussionCategoryPage":
            case "nestedCategoryList":
            default:
                $filterTypeSchemaExtraOptions = [];
                break;
        }

        return $filterTypeSchemaExtraOptions;
    }
}
