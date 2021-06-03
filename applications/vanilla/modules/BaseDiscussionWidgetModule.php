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

/**
 * Class AbstractRecordTypeModule
 *
 * @package Vanilla\Community
 */
class BaseDiscussionWidgetModule extends AbstractReactModule {

    use HomeWidgetContainerSchemaTrait;

    /** @var \DiscussionsApiController */
    protected $discussionsApi;

    /** @var array Parameters to pass to the API */
    protected $apiParams = [];

    /** @var string */
    protected $title;

    /** @var string */
    protected $subtitle;

    /** @var string */
    protected $description;

    /** @var string */
    protected $viewAllUrl = '/discussions';

    /** @var null|array[] */
    protected $discussions = null;


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
        $apiParams = $this->getRealApiParams();

        if ($this->discussions === null) {
            try {
                $this->discussions = $this->discussionsApi->index($apiParams);
            } catch (PermissionException $e) {
                // A user might not have permission to see this.
                return null;
            }
        }

        $props = [
            'apiParams' => $apiParams,
            'discussions' => $this->discussions,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'description' => $this->description,
        ];

        return $props;
    }

    /**
     * Get react component name
     *
     * @return string
     */
    public function getComponentName(): string {
        return "DiscussionListModule";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        $schema = SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(),
            Schema::parse([
                'apiParams' => static::getApiSchema()
            ])
        );

        return $schema;
    }

    /**
     * Get the real parameters that we will pass to the API.
     *
     * @return array
     */
    protected function getRealApiParams(): array {
        $apiParams = $this->apiParams;
        $apiParams = $this->getApiSchema()->validate($apiParams);

        // Handle the slotType.
        $slotType = $apiParams['slotType'] ?? '';
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
     * Get the schema of our api params.
     *
     * @return Schema
     */
    public static function getApiSchema(): Schema {
        $apiSchema = new Schema([
            'type' => 'object',
            'default' => new \stdClass(),
        ]);
        $apiSchema->setField('x-control', SchemaForm::section(
            new FormOptions('Settings and Filters', 'Apply filters and settings.')
        ));

        return $apiSchema;
    }

    /**
     * Get categorySchema.
     *
     * @return Schema
     */
    protected static function categorySchema(): Schema {
        return Schema::parse([
            'categoryID?' => [
                'type' => ['integer', 'null'],
                'default' => null,
                'x-control' => DiscussionsApiIndexSchema::getCategoryIDFormOptions(
                    new FieldMatchConditional(
                        'apiParams',
                        Schema::parse([
                            'siteSectionID' => [
                                'type' => 'null'
                            ]
                        ])
                    )
                )],
            'includeChildCategories?' => [
                'type' => 'boolean',
                'default' => true,
                'x-control' => SchemaForm::toggle(
                    new FormOptions(
                        t('Include Child Categories'),
                        t('Include records from child categories.')
                    ),
                    new FieldMatchConditional(
                        'apiParams',
                        Schema::parse([
                            'categoryID' => [
                                'type' => 'integer',
                            ],
                            'siteSectionID' => [
                                'type' => 'null'
                            ]
                        ])
                    )
                )]
        ]);
    }

    /**
     * Get site-section-id schema.
     *
     * @return Schema
     */
    protected static function siteSectionIDSchema(): Schema {
        return Schema::parse([
            'siteSectionID?' => [
                'type' => ['string', 'null'],
                'default' => null,
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(t('Subcommunity'), t('Display records from this subcommunity.')),
                    new StaticFormChoices(self::getSiteSectionFormChoices()),
                    new FieldMatchConditional(
                        'apiParams',
                        Schema::parse([
                            'categoryID' => [
                                'type' => 'null',
                            ],
                        ])
                    )
                )
            ]
        ]);
    }

    /**
     * Get sort schema.
     *
     * @return Schema
     */
    protected static function sortSchema(): Schema {
        return Schema::parse([
            'sort?' => [
                'type' => 'string',
                'default' => '-dateInserted',
                'x-control' => DiscussionsApiIndexSchema::getSortFormOptions()
            ]
        ]);
    }

    /**
     * Get limit Schema
     *
     * @return Schema
     */
    protected static function limitSchema(): Schema {
        return Schema::parse([
            'limit?' => [
                'type' => 'integer',
                'default' => 10,
                'x-control' => DiscussionsApiIndexSchema::getLimitFormOptions()
            ]
        ]);
    }

    /**
     * Get slotType schema.
     *
     * @return Schema
     */
    protected static function getSlotTypeSchema(): Schema {
        return Schema::parse([
            'slotType?' => [
                'type' => 'string',
                'default' => 'a',
                'enum' => ['d', 'w', 'm', 'a'],
                'x-control' => [
                    SchemaForm::radio(
                        new FormOptions(
                            t('Timeframe'),
                            t('Choose when to load records from.')
                        ),
                        new StaticFormChoices(
                            [
                                'd' => t('Last Day'),
                                'w' => t('Last Week'),
                                'm' => t('Last Month'),
                                'a' => t('All Time'),
                            ]
                        ),
                        new FieldMatchConditional(
                            'apiParams.sort',
                            Schema::parse([
                                'type' => 'string',
                                'const' => '-score'
                            ])
                        )
                    ),
                    SchemaForm::radio(
                        new FormOptions(
                            t('Timeframe'),
                            t('Choose when to load discussions from.')
                        ),
                        new StaticFormChoices(
                            [
                                'd' => t('Last Day'),
                                'w' => t('Last Week'),
                                'm' => t('Last Month'),
                                'a' => t('All Time'),
                            ]
                        ),
                        new FieldMatchConditional(
                            'apiParams.sort',
                            Schema::parse([
                                'type' => 'string',
                                'const' => '-hot'
                            ])
                        )
                    )
                ]
            ]
        ]);
    }

    /**
     * Get all site-sections form choices.
     *
     * @return array
     */
    protected static function getSiteSectionFormChoices(): array {
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

            if ($id !== (string)DefaultSiteSection::DEFAULT_ID) {
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
    public static function getWidgetName(): string {
        return "Discussions List";
    }

    ///
    /// Setters
    ///

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
     * Apply discussions that were already fetched from the API.
     *
     * @param array[] $discussions
     */
    public function setDiscussions(array $discussions): void {
        $this->discussions = $discussions;
    }
}
