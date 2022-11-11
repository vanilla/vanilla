<?php
/**
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Forum\Widgets\RssWidgetTrait;
use Vanilla\InjectableInterface;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\AbstractHomeWidgetModule;

/**
 * Module displaying RSS feed items.
 */
class RSSModule extends AbstractHomeWidgetModule implements InjectableInterface
{
    use RssWidgetTrait;

    const RSS_FEED_MODULE = "RSS feed";

    /**
     * RSS feed url.
     *
     * @var string
     */
    private $url;

    public $contentType = self::CONTENT_TYPE_IMAGE;

    /**
     * @var string|null
     */
    private $viewAllUrl;

    /**
     * @var string|null
     */
    private $fallbackImage;

    /**
     * RSSModule constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->moduleName = self::RSS_FEED_MODULE;
        $this->title = t("RSS Feed");
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getViewAllUrl(): ?string
    {
        return $this->viewAllUrl;
    }

    /**
     * @param string|null $viewAllUrl
     */
    public function setViewAllUrl(?string $viewAllUrl): void
    {
        $this->viewAllUrl = $viewAllUrl;
    }

    /**
     * @return string|null
     */
    public function getFallbackImage(): ?string
    {
        return $this->fallbackImage;
    }

    /**
     * @param string|null $fallbackImage
     */
    public function setFallbackImage(?string $fallbackImage): void
    {
        $this->fallbackImage = $fallbackImage;
    }

    /**
     * @return string
     */
    public function assetTarget()
    {
        return "Content";
    }

    /**
     * @return array|null
     */
    protected function getData(): ?array
    {
        return $this->getRssFeedItems($this->getUrl(), $this->getFallbackImage());
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string
    {
        return "RSS Feed";
    }

    /**
     * @inheritDoc
     */
    protected function getContainerOptions(): array
    {
        $containerOptions = parent::getContainerOptions();
        if ($this->getViewAllUrl()) {
            $containerOptions["viewAll"] = [
                "to" => $this->getViewAllUrl(),
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
    public static function widgetMaxCountItemSchema(int $defaultMaxItemCount = 3)
    {
        $choices = array_combine(range(1, 10), range(1, 10));

        return Schema::parse([
            "maxItemCount:i?" => [
                "default" => $defaultMaxItemCount,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Limit", "Maximum amount of items to display."),
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
    public static function widgetViewAllUrlSchema(): Schema
    {
        return Schema::parse([
            "viewAllUrl:s?" => [
                "x-control" => SchemaForm::textBox(new FormOptions("View All URL", "Set View All URL."), "url"),
            ],
        ]);
    }

    /**
     * Content type widget schema.
     *
     * @return Schema
     */
    public static function widgetContentTypeSchema()
    {
        $contentTypes = [
            self::CONTENT_TYPE_IMAGE => "Image",
            self::CONTENT_TYPE_ICON => "Icon",
            self::CONTENT_TYPE_BACKGROUND => "Background",
        ];
        $enumContentTypes = array_keys($contentTypes);

        return Schema::parse([
            "contentType:s?" => [
                "enum" => $enumContentTypes,
                "default" => self::CONTENT_TYPE_IMAGE,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Display Type", "Choose the appearance of your widget."),
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
    public static function getWidgetSchema(): Schema
    {
        $widgetSchema = Schema::parse([
            "url:s" => [
                "x-control" => SchemaForm::textBox(new FormOptions("URL", "RSS Feed URL."), "url"),
            ],
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
