<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2008-2021 Vanilla Forums, Inc.
 * @license Proprietary
 */

namespace Vanilla\Community;

use Garden\Schema\Schema;
use Vanilla\Forms\FormOptions;
use Vanilla\Forms\SchemaForm;
use Vanilla\Forms\StaticFormChoices;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\AbstractHomeWidgetModule;

/**
 * Class CallToActionModule
 *
 * @package Vanilla\Community
 */
class CallToActionModule extends AbstractReactModule {

    /** @var string */
    private $textCTA;
    /** @var string */
    private $url;
    /** @var string |null */
    private $imageUrl;
    /** @var string */
    private $title;
    /** @var string|null */
    private $description;

    /** @var string|null */
    private $imagePlacement;

    /**
     * @var string|null
     */
    private $linkButtonType = null;

    /**
     * @var string|null Explicitly pass border type.
     */
    private $borderType = null;

    /**
     * @var array| null
     * Each value is an array of:
     * to: (string) CTA link. Required.
     * textCTA: (string) CTA text. Required.
     * linkButtonType: (string) CTA link. Optional.
     */
    private $otherCTAs;

    /**
     * @return string
     */
    public function getTextCTA(): string {
        return $this->textCTA;
    }

    /**
     * @param string $textCTA
     */
    public function setTextCTA(string $textCTA): void {
        $this->textCTA = $textCTA;
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
    public function getImageUrl(): ?string {
        return $this->imageUrl;
    }

    /**
     * @param string|null $imageUrl
     */
    public function setImageUrl(?string $imageUrl): void {
        $this->imageUrl = $imageUrl;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getImagePlacement(): ?string {
        return $this->imagePlacement;
    }

    /**
     * @param string|null $imagePlacement
     */
    public function setImagePlacement(?string $imagePlacement): void {
        $this->imagePlacement = $imagePlacement;
    }

    /**
     * @return string|null
     */
    public function getBorderType(): ?string {
        return $this->borderType;
    }

    /**
     * @param string|null $borderType
     */
    public function setBorderType(?string $borderType): void {
        $this->borderType = $borderType;
    }

    /**
     * @return array|null
     */
    public function getOtherCTAs(): ?array {
        return $this->otherCTAs;
    }

    /**
     * @param array|null $otherCTAs
     */
    public function setOtherCTAs(?array $otherCTAs = null): void {
        $this->otherCTAs = $otherCTAs;
    }

    /**
     * @return string
     */
    public function getLinkButtonType(): ?string {
        return $this->linkButtonType;
    }

    /**
     * @param string $linkButtonType
     */
    public function setLinkButtonType(string $linkButtonType): void {
        $this->linkButtonType = $linkButtonType;
    }

    /**
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * @inheritDoc
     */
    public function getComponentName(): string {
        return 'CallToAction';
    }

    /**
     * @return Schema
     */
    public static function widgetUrlSchema(): Schema {
        return Schema::parse([
            'url:s' => [
                'x-control' => SchemaForm::textBox(new FormOptions('Url', 'Set an url.')),
            ],
        ]);
    }

    /**
     * @return Schema
     */
    public static function widgetTextCTASchema(): Schema {
        return Schema::parse([
            'textCTA:s?' => [
                'x-control' => SchemaForm::textBox(new FormOptions('Text CTA', 'Set text CTA.')),
            ],
        ]);
    }

    /**
     * @return Schema
     */
    public static function widgetImageUrlSchema(): Schema {
        return Schema::parse([
            'imageUrl:s?' => [
                'x-control' => SchemaForm::textBox(new FormOptions('Image Url', 'Set an image url.')),
            ],
            'imagePlacement:s?' => [
                'default' => 'top',
                'x-control' => SchemaForm::dropDown(
                    new FormOptions('Image placement', 'Choose an image position.'),
                    new StaticFormChoices(['top' => 'Top', 'left' => 'Left'])
                ),
            ],
        ]);
    }


    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema {
        return SchemaUtils::composeSchemas(
            self::widgetUrlSchema(),
            self::widgetTextCTASchema(),
            AbstractHomeWidgetModule::widgetTitleSchema(),
            AbstractHomeWidgetModule::widgetDescriptionSchema(),
            self::widgetImageUrlSchema()
        );
    }


    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "Call To Action";
    }


    /**
     * Create a schema of the props for the component.
     *
     * @return Schema
     */
    public static function getSchema(): Schema {
        return Schema::parse([
            'to:s',
            'textCTA:s',
            'title:s',
            'imageUrl:s?',
            'description:s?',
            'otherCTAs:a?' => Schema::parse([
                'to:s',
                'textCTA:s',
                'linkButtonType:s?',
            ]),
            'options:?' => Schema::parse([
                'box:?' => [
                    'borderType:s?',
                ],
                'imagePlacement:s?' => [
                    'enum' => ['top', 'left'],
                ],
                'linkButtonType:s?',
            ]),
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array {
        $props = [
            'to' => $this->getUrl(),
            'textCTA' => $this->getTextCTA(),
            'title' => $this->getTitle(),
            'imageUrl' => $this->getImageUrl() ?? '',
            'description' => $this->getDescription() ?? '',
            'options' => $this->getOptions(),
            'otherCTAs' => $this->getOtherCTAs(),
        ];
        $props = $this->getSchema()->validate($props);

        return $props;
    }

    /**
     * Get image properties. Set the placement.
     *
     * @return array
     */
    protected function getOptions(): array {
        $options = [
            'imagePlacement' => $this->getImagePlacement(),
        ];
        if ($this->getBorderType()) {
            $options['box'] = [
                'borderType' => $this->getBorderType(),
            ];
        }
        if ($this->getLinkButtonType()) {
            $options['linkButtonType'] = $this->getLinkButtonType();
        }

        return $options;
    }
}
