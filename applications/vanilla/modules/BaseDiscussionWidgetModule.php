<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\CurrentTimeStamp;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Widgets\DiscussionsWidgetSchemaTrait;
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
    use HomeWidgetContainerSchemaTrait, WidgetSchemaTrait, DiscussionsWidgetSchemaTrait;

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
     * Get props for component
     *
     * @param array|null $params
     * @return array|null
     */
    public function getProps(?array $params = null): ?array
    {
        $apiParams = $this->getRealApiParams($params["apiParams"] ?? null);

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
            "title" => $params["title"] ?? $this->title,
            "subtitle" => $params["subTitle"] ?? $this->subtitle,
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
     */
    protected function getRealApiParams(?array $params = null): array
    {
        $apiParams = $params ?? $this->apiParams;
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

        // Hide discussions from hidden categories
        $apiParams["excludeHiddenCategories"] = true;

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
