<?php
/**
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use DOMDocument;
use Garden\Schema\Schema;
use Gdn_Cache;
use Vanilla\Dashboard\Models\RemoteResourceModel;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Metadata\Parser\RSSFeedParser;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\AbstractHomeWidgetModule;

/**
 * Module displaying RSS feed items.
 */
class RSSModule extends AbstractHomeWidgetModule {

    const RSS_FEED_MODULE = "RSS feed";

    /**
     * RSS feed url.
     *
     * @var string
     */
    private $url;
    /**
     * @var RSSFeedParser
     */
    private $feedParser;
    /**
     * @var FormatService
     */
    private $formatService;

    public $contentType = self::CONTENT_TYPE_IMAGE;
    /**
     * @var RemoteResourceModel
     */
    private $remoteResourceModel;

    /**
     * @var string|null
     */
    private $viewAllUrl;
    /**
     * @var Gdn_Cache
     */
    private $cache;

    /**
     * @var string|null
     */
    private $fallbackImage;

    /**
     * RSSModule constructor.
     *
     * @param RSSFeedParser $feedParser
     * @param FormatService $formatService
     * @param RemoteResourceModel $remoteResourceModel
     * @param Gdn_Cache $cache
     */
    public function __construct(
        RSSFeedParser $feedParser,
        FormatService $formatService,
        RemoteResourceModel $remoteResourceModel,
        Gdn_Cache $cache
    ) {
        parent::__construct();
        $this->moduleName = self::RSS_FEED_MODULE;
        $this->feedParser = $feedParser;
        $this->formatService = $formatService;
        $this->title = t('RSS Feed');
        $this->remoteResourceModel = $remoteResourceModel;
        $this->cache = $cache;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getViewAllUrl(): ?string {
        return $this->viewAllUrl;
    }

    /**
     * @param string|null $viewAllUrl
     */
    public function setViewAllUrl(?string $viewAllUrl): void {
        $this->viewAllUrl = $viewAllUrl;
    }

    /**
     * @return string|null
     */
    public function getFallbackImage(): ?string {
        return $this->fallbackImage;
    }

    /**
     * @param string|null $fallbackImage
     */
    public function setFallbackImage(?string $fallbackImage): void {
        $this->fallbackImage = $fallbackImage;
    }

    /**
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * Get the cache key based on the url.
     *
     * @return string
     */
    private function getCacheKey(): string {
        $url = $this->getUrl();

        return sprintf('rss.module.%s.parse.content', $url);
    }

    /**
     * Get parsed  xml data from the cache.
     *
     * @return array|null
     */
    private function getFromCache(): ?array {
        $key = $this->getCacheKey();
        $parsedContent = $this->cache->get($key);

        return $parsedContent !== Gdn_Cache::CACHEOP_FAILURE ? $parsedContent : null;
    }

    /**
     * Load parsed xml data.
     *
     * @return array|null
     */
    private function loadParsedXMLData(): ?array {
        $url = $this->getUrl();
        $results = $this->getFromCache();
        $rssFeedContent = !$results ? $this->remoteResourceModel->getByUrl($url) : null;
        if (!$results && !$rssFeedContent) {
            return null;
        }
        if (!$results && $rssFeedContent) {
            $rssFeedDOM = new DOMDocument();
            $loaded = $rssFeedDOM->loadXML($rssFeedContent);
            if ($loaded) {
                $results = $this->feedParser->parse($rssFeedDOM);
                $key = $this->getCacheKey();
                $this->cache->store($key, $results, [Gdn_Cache::FEATURE_EXPIRY => Gdn_Cache::APC_CACHE_DURATION]);
            }
        }

        return $results;
    }

    /**
     * @return array|null
     */
    protected function getData(): ?array {
        $results = $this->loadParsedXMLData();
        if (!$results) {
            return null;
        }
        $resultsItems = $results['item'] ?? [];
        // Set fallback image with channel image.
        $channelFallbackImage = $results['channel']['image'] ?? null;
        if (!$this->getFallbackImage() && $channelFallbackImage) {
            $this->setFallbackImage($channelFallbackImage['url']);
        }

        return array_map([$this, 'mapRSSFeedToItem'], $resultsItems);
    }

    /**
     * Map RSS feed data into a widget item.
     *
     * @param array $result
     * @return array
     */
    private function mapRSSFeedToItem(array $result) {
        $image = $result['img'] ?? null;
        $imageValue = $image['src'] ?? null;
        $enclosure = $result['enclosure'] ?? null;
        $isEnclosureImage = $enclosure && substr($enclosure['type'], 0, 6) === 'image/';
        $enclosureValue = $isEnclosureImage ? $enclosure['url'] : null;
        $imageUrl = $imageValue ?: $enclosureValue;

        return [
            'to' => $result['link'],
            'name' => $result['title'],
            'imageUrl' => $imageUrl ?: $this->getFallbackImage(),
            'description' => $this->formatService->renderPlainText($result['description'], HtmlFormat::FORMAT_KEY),
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "RSS Feed";
    }

    /**
     * @inheritDoc
     */
    protected function getContainerOptions(): array {
        $containerOptions = parent::getContainerOptions();
        if ($this->getViewAllUrl()) {
            $containerOptions['viewAll'] = [
                'to' => $this->getViewAllUrl(),
            ];
        }

        return $containerOptions;
    }

    /**
     * Set Max Count Item widget schema.
     *
     * @param int $defaultMaxItemCount
     * @return Schema
     */
    public static function widgetMaxCountItemSchema(int $defaultMaxItemCount = 3) {
        $choices = array_combine(range(1, 10), range(1, 10));

        return Schema::parse([
            'maxItemCount:i?' => [
                'default' => $defaultMaxItemCount,
                'x-control' => SchemaForm::dropDown(
                    new FormOptions('Limit', 'Maximum amount of items to display.'),
                    new StaticFormChoices($choices)
                ),
            ],
        ]);
    }

    /**
     * View All Url schema.
     *
     * @return Schema
     */
    public static function widgetViewAllUrlSchema(): Schema {
        return Schema::parse([
            'viewAllUrl:s?' => [
                'x-control' => SchemaForm::textBox(
                    new FormOptions(
                        'View All URL',
                        'Set View All URL.'
                    ),
                    "url"
                ),
            ],
        ]);
    }

    /**
     * Content type widget schema.
     *
     * @return Schema
     */
    public static function widgetContentTypeSchema() {
        $contentTypes = [
            self::CONTENT_TYPE_IMAGE => 'Image',
            self::CONTENT_TYPE_ICON => 'Icon',
            self::CONTENT_TYPE_BACKGROUND => 'Background',
        ];
        $enumContentTypes = array_keys($contentTypes);

        return Schema::parse([
            'contentType:s?' => [
                'enum' => $enumContentTypes,
                'default' => self::CONTENT_TYPE_IMAGE,
                'x-control' => SchemaForm::dropDown(
                    new FormOptions(
                        'Display Type',
                        'Choose the appearance of your widget.'
                    ),
                    new StaticFormChoices($contentTypes)
                ),
            ],
        ]);
    }

    /**
     * Define RSS Feed widget schema.
     *
     * @return Schema
     */
    public static function getWidgetSchema(): Schema {
        $widgetSchema = Schema::parse([
            'url:s' => [
                'x-control' => SchemaForm::textBox(
                    new FormOptions(
                        'URL',
                        'RSS Feed URL.'
                    ),
                    "url"
                ),
            ]
        ]);

        return SchemaUtils::composeSchemas(
            $widgetSchema,
            self::widgetTitleSchema(),
            self::widgetSubtitleSchema(),
            self::widgetDescriptionSchema(),
            self::widgetViewAllUrlSchema(),
            self::widgetMaxCountItemSchema(),
            self::widgetColumnSchema(),
            self::widgetContentTypeSchema()
        );
    }
}
