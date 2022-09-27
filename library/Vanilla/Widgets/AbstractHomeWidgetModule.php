<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Widgets;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;

/**
 * Abstraction layer for the module displaying Categories.
 */
abstract class AbstractHomeWidgetModule extends AbstractReactModule
{
    use HomeWidgetContainerSchemaTrait;

    const CONTENT_TYPE_ICON = "title-description-icon";
    const CONTENT_TYPE_IMAGE = "title-description-image";
    const CONTENT_TYPE_BACKGROUND = "title-background";
    const CONTENT_TYPE_TEXT = "title-description";
    const ALIGNMENT_LEFT = "left";
    const ALIGNMENT_CENTER = "center";
    const DEFAULT_MAX_ITEMS_COUNT = 5;

    /** @var string|null */
    public $title = null;

    /**
     * @var int|null Max number of columns in the widget.
     */
    public $maxColumnCount = null;

    /**
     * @var string|null The display content type.
     */
    public $contentType = null;

    /**
     * @var array|null Some styling options for CONTENT_TYPE_ICON.
     */
    public $iconProps = [];

    /**
     * @var array|null Subtitle with an option for its placement.
     */
    public $subtitle = [];

    /** @var string|null */
    public $description = null;

    /** @var string|null */
    public $headerAlignment = null;

    /** @var string|null */
    public $contentAlignment = null;

    /** @var bool */
    public $noGutter = false;

    /**
     * @var string|null Defines content width.
     */
    public $maxWidth = null;

    /**
     * @var object|null Whether counts or description is rendered.
     */
    public $display = null;

    /**
     * @var object|null Item name styling options.
     */
    public $name = null;

    /**
     * @var string|null Explicitly pass border type.
     */
    public $borderType = null;

    /**
     * @var array|null The container options for the widget.
     */
    public $containerOptions = [];

    /**
     * @var array The item options for the widget.
     */
    public $itemOptions = [];

    /**
     * @var int|null
     */
    private $maxItemCount = null;

    /** @var bool */
    private $isCarousel = false;

    /**
     * @return array|null
     */
    abstract protected function getData(): ?array;

    /**
     * @return string|null
     */
    protected function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return int|null
     */
    public function getMaxItemCount(): ?int
    {
        return $this->maxItemCount;
    }

    /**
     * @param int|null $maxItemCount
     */
    public function setMaxItemCount(?int $maxItemCount = null): void
    {
        $this->maxItemCount = $maxItemCount;
    }

    /**
     * @return array
     */
    protected function getItemOptions(): array
    {
        $options = array_merge_recursive(
            [
                "contentType" => $this->contentType,
                "display" => $this->display,
                "box" => [
                    "borderType" => $this->borderType,
                ],
                "name" => $this->name,
            ],
            $this->itemOptions
        );
        if (!empty($this->iconProps)) {
            $options["iconProps"] = $this->iconProps;
        }
        return $options;
    }

    /**
     * @return array
     */
    protected function getContainerOptions(): array
    {
        $this->containerOptions = array_merge(
            [
                "maxColumnCount" => $this->maxColumnCount,
                "subtitle" => $this->subtitle,
                "description" => $this->description,
                "headerAlignment" => $this->headerAlignment,
                "contentAlignment" => $this->contentAlignment,
                "maxWidth" => $this->maxWidth,
                "noGutter" => $this->noGutter,
                "isCarousel" => $this->isCarousel,
            ],
            $this->containerOptions
        );

        return $this->containerOptions;
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array
    {
        $data = $this->getData();
        if ($data === null) {
            return null;
        }
        if (count($data) === 0) {
            return null;
        }
        $props = [];
        $props["title"] = $this->getTitle();
        $props["containerOptions"] = $this->getContainerOptions();
        $props["itemOptions"] = $this->getItemOptions();
        $props["itemData"] = $data;
        $props["maxItemCount"] = $this->getMaxItemCount();

        $props = $this->getSchema()->validate($props);

        return $props;
    }

    /**
     * Get name of component to render.
     *
     * @return string
     */
    public static function getComponentName(): string
    {
        return "HomeWidget";
    }

    /**
     * Create a schema of the props for the component.
     *
     * @return Schema
     */
    public static function getSchema(): Schema
    {
        $bgSchema = Schema::parse([
            "color:s?",
            "attachment:s?",
            "position:s?",
            "repeat:s?",
            "size:s?",
            "attachment:s?",
            "position:s?",
            "repeat:s?",
            "size:s?",
            "image:s?",
            "fallbackImage:s?",
            "opacity:i?",
            "unsetBackground:b?",
        ]);

        return Schema::parse([
            "title:s?",
            "containerOptions" => Schema::parse([
                "outerBackground:?" => $bgSchema,
                "innerBackground:?" => $bgSchema,
                "borderType:s?",
                "maxWidth:s?",
                "noGutter:b?",
                "viewAll?" => Schema::parse(["position:s?", "to:s?", "name:s?", "displayType:s?"]),
                "maxColumnCount:i?",
                "subtitle?" => Schema::parse([
                    "type:s?" => [
                        "enum" => ["standard", "overline"],
                    ],
                    "content:s?",
                    "font:o?",
                    "padding:o?",
                ]),
                "description:s?",
                "headerAlignment:s?" => [
                    "enum" => ["center", "left"],
                ],
                "contentAlignment:s?" => [
                    "enum" => ["center", "flex-start"],
                ],
                "isCarousel:b?",
            ]),
            "itemOptions" => Schema::parse([
                "imagePlacement:s?" => [
                    "enum" => ["left", "top"],
                ],
                "imagePlacementMobile:s?" => [
                    "enum" => ["left", "top"],
                ],
                "box?" => ["borderType:s?", "border:o?", "background:?" => $bgSchema, "spacing:o?"],
                "contentType:s?",
                "fg:s?",
                "display:?" => Schema::parse(["description:b", "counts:b"]),
                "name:?" => Schema::parse(["hidden:b?", "font:o?", "states:o?"]),
                "justifyContent:s?",
                "alignment:s?",
                "viewMore?" => Schema::parse(["labelCode:s?", "buttonType:s?"]),
                "iconProps?" => Schema::parse([
                    "placement:s?" => [
                        "enum" => ["top", "left"],
                    ],
                    "background:o?",
                    "border:o?",
                    "size:i?",
                ]),
            ]),
            "maxItemCount:i?",
            "itemData:a" => Schema::parse([
                "to:s",
                "iconUrl:s?",
                "imageUrl:s?",
                "name:s",
                "description:s?",
                "counts:a?" => Schema::parse(["labelCode:s", "count:i"]),
            ]),
        ]);
    }

    /**
     * @param bool $isCarousel
     */
    public function setIsCarousel(bool $isCarousel)
    {
        $this->isCarousel = $isCarousel;
    }

    /**
     * @param string $content
     */
    public function setSubtitleContent(string $content)
    {
        $this->subtitle["content"] = $content;
    }

    /**
     * Get schema for the number of columns.
     *
     * @return Schema
     */
    public static function widgetColumnSchema(): Schema
    {
        return Schema::parse([
            "maxColumnCount:i?" => [
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Max Columns", "Set the maximum number of columns for the widget."),
                    new StaticFormChoices(["1" => 1, "2" => 2, "3" => 3, "4" => 4, "5" => 5])
                ),
            ],
        ]);
    }

    /**
     * @return Schema
     */
    public static function widgetContentTypeSchema()
    {
        return Schema::parse([
            "contentType:s?" => [
                "enum" => [
                    self::CONTENT_TYPE_TEXT,
                    self::CONTENT_TYPE_ICON,
                    self::CONTENT_TYPE_BACKGROUND,
                    self::CONTENT_TYPE_IMAGE,
                ],
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Display Type", "Choose the appearance of your widget."),
                    new StaticFormChoices([
                        self::CONTENT_TYPE_TEXT => "Text",
                        self::CONTENT_TYPE_IMAGE => "Image",
                        self::CONTENT_TYPE_ICON => "Icon",
                        self::CONTENT_TYPE_BACKGROUND => "Background",
                    ])
                ),
            ],
        ]);
    }

    /**
     * @return Schema
     */
    public static function widgetHeaderAligmentSchema()
    {
        return Schema::parse([
            "headerAlignment:s?" => [
                "enum" => [self::ALIGNMENT_LEFT, self::ALIGNMENT_CENTER],
                "default" => self::ALIGNMENT_LEFT,
                "x-control" => SchemaForm::dropDown(
                    new FormOptions("Header alignment", "Choose header alignment."),
                    new StaticFormChoices([
                        self::ALIGNMENT_LEFT => "Left",
                        self::ALIGNMENT_CENTER => "Center",
                    ])
                ),
            ],
        ]);
    }

    /**
     * Set Max Count Item widget schema.
     *
     * @param int $defaultMaxItemCount
     * @return Schema
     */
    public static function widgetMaxCountItemSchema(int $defaultMaxItemCount = self::DEFAULT_MAX_ITEMS_COUNT)
    {
        return Schema::parse([
            "maxItemCount:i?" => [
                "default" => $defaultMaxItemCount,
                "x-control" => SchemaForm::textBox(
                    new FormOptions("Limit", "Maximum amount of items to display."),
                    "number"
                ),
            ],
        ]);
    }

    /**
     * Get a schema for the carousel.
     *
     * @return Schema
     */
    public static function getCarouselSchema()
    {
        return Schema::parse([
            "isCarousel:b?" => [
                "default" => false,
                "x-control" => SchemaForm::toggle(new FormOptions("As Carousel", "Display the widget as a carousel.")),
            ],
        ]);
    }

    /**
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(),
            self::widgetDescriptionSchema(),
            self::widgetSubtitleSchema(),
            self::widgetColumnSchema(),
            self::widgetContentTypeSchema(),
            self::getCarouselSchema()
        );
    }
}
