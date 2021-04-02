<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Modules;

use Garden\JsonFilterTrait;
use Garden\Schema\Schema;
use Vanilla\CurrentTimeStamp;
use Vanilla\Exception\PermissionException;
use Vanilla\Forms\FieldMatchConditional;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Controllers\Api\DiscussionsApiIndexSchema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;

/**
 *
 */
class DiscussionListModule extends AbstractReactModule {

    use HomeWidgetContainerSchemaTrait;
    use JsonFilterTrait;

    /** @var \DiscussionsApiController */
    private $discussionsApi;

    /** @var array Parameters to pass to the API */
    private $apiParams = [];

    /** @var string */
    private $title;

    /** @var string */
    private $subtitle;

    /** @var string */
    private $description;

    /** @var string */
    private $viewAllUrl = '/discussions';

    /** @var null|array[] */
    private $discussions = null;

    /**
     * DI.
     *
     * @param \DiscussionsApiController $discussionsApi
     */
    public function __construct(\DiscussionsApiController $discussionsApi) {
        parent::__construct();
        $this->discussionsApi = $discussionsApi;
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array {
        if ($this->discussions === null) {
            $apiParams = $this->getRealApiParams();
            try {
                $this->discussions = $this->discussionsApi->index($apiParams);
            } catch (PermissionException $e) {
                // A user might not have permission to see this.
                return null;
            }
        } else {
            // We don't know the API params, but make sure they were unique so the frontend doesn't mix them up.
            $apiParams = ['rand' => randomString(10)];
        }

        // Make sure our data gets the same filtering as if we had requested from the API.
        // Most notably this fixed up dates.
        $this->discussions = $this->jsonFilter($this->discussions);

        $props = [
            'apiParams' => $apiParams,
            'discussions' => $this->discussions,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
            'viewAllUrl' => $this->viewAllUrl,
        ];

        return $props;
    }

    /**
     * Get the real parameters that we will pass to the API.
     *
     * @return array
     */
    private function getRealApiParams(): array {
        $apiParams = $this->apiParams;
        $apiParams = $this->getApiSchema()->validate($apiParams);

        // Handle the slotType.
        $slotType = $apiParams['slotType'];
        $currentTime = CurrentTimeStamp::getDateTime();
        $filterTime = null;
        switch ($slotType) {
            case 'w':
                $filterTime = $currentTime->modify('-1 week');
                break;
            case 'm':
                $filterTime = $currentTime->modify('-1 month');
                break;
            case 'y':
                $filterTime = $currentTime->modify('-1 year');
                break;
            case 'a':
            default:
                break;
        }
        // Not a real API parameter.
        unset($apiParams['slotType']);
        if ($filterTime !== null) {
            // Convert into an API filter.
            $formattedTime = $filterTime->format(\DateTime::RFC3339_EXTENDED);
            $apiParams['dateInserted'] = ">$formattedTime";
        }

        // Force some common expands
        // Default sort.
        $apiParams['sort'] = $apiParams['sort'] ?? 'dateLastComment';
        $apiParams['expand'] = ['category', 'insertUser', 'lastUser', '-body', 'excerpt', 'tags'];

        return $apiParams;
    }


    /**
     * @inheritdoc
     */
    public function getComponentName(): string {
        return "DiscussionListModule";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema('Discussions'),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(),
            Schema::parse([
                'apiParams' => self::getApiSchema(),
            ])
        );
    }

    /**
     * Get the schema of our api params.
     *
     * @return Schema
     */
    private static function getApiSchema(): Schema {
        $apiSchema = new DiscussionsApiIndexSchema(10);
        $apiSchema->setField('x-control', SchemaForm::section(
            new FormOptions('API Parameters', 'Configure how the data is fetched.')
        ));
        $apiSchema = $apiSchema->merge(Schema::parse([
            'slotType?' => [
                'type' => 'string',
                'default' => 'a',
                'enum' => ['d', 'w', 'm', 'a'],
                'x-control' => SchemaForm::radio(
                    new FormOptions(
                        'Timeframe',
                        'Choose when to load discussions from.'
                    ),
                    new StaticFormChoices(
                        [
                            'd' => 'Last Day',
                            'w' => 'Last Week',
                            'm' => 'Last Month',
                            'a' => 'All Time',
                        ]
                    )
                )
            ],
        ]));
        return $apiSchema;
    }

    /**
     * @return string
     */
    public static function getWidgetName(): string {
        return "Discussions List";
    }

    ///
    /// Setters
    ///

    /**
     * Apply discussions that were already fetched from the API.
     *
     * @param array[] $discussions
     */
    public function setDiscussions(array $discussions): void {
        $this->discussions = $discussions;
    }

    /**
     * @param array $apiParams
     */
    public function setApiParams(array $apiParams): void {
        $this->apiParams = $apiParams;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        $this->title = $title;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitle(string $subtitle): void {
        $this->subtitle = $subtitle;
    }

    /**
     * @param string $subtitle
     */
    public function setSubtitleContent(string $subtitle): void {
        $this->subtitle = $subtitle;
    }
    /**
     * @param string $viewAllUrl
     */
    public function setViewAllUrl(string $viewAllUrl): void {
        $this->viewAllUrl = $viewAllUrl;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description): void {
        $this->description = $description;
    }

    /**
     * Apply a full set of options from a shim.
     *
     * @param FoundationShimOptions $options
     */
    public function applyOptions(FoundationShimOptions $options) {
        $this->title = $options->getTitle();
        $this->description = $options->getDescription();
        $this->viewAllUrl = $options->getViewAllUrl();
    }
}
