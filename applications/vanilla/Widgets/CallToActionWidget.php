<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\Fragments\CallToActionFragmentMeta;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\React\ReactWidget;
use Vanilla\Widgets\WidgetSchemaTrait;

/**
 * Class CallToActionWidget
 */
class CallToActionWidget extends ReactWidget
{
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use CallToActionWidgetTrait;

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [CallToActionFragmentMeta::class];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Call to Action";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "cta";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Call To Action";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "CallToActionWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/cta.svg";
    }

    /**
     * Get props for component
     *
     * @return array
     */
    public function getProps(): ?array
    {
        $backgroundImage = $this->props["background"]["image"] ?? null;
        if ($backgroundImage) {
            $this->props["background"]["imageUrlSrcSet"] = $this->getImageSrcSet($backgroundImage);
        }

        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(null, true, "Call to Action Title"),
            self::widgetDescriptionSchema(
                null,
                false,
                "What does this action do? Describe the value to your community members."
            ),
            self::getWidgetSpecificSchema()
        );
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $links = array_filter([
            $props["button"] ?? null,
            $props["firstButton"] ?? null,
            $props["secondButton"] ?? null,
        ]);
        $result = $this->renderWidgetContainerSeoContent($props, $this->renderSeoLinkList($links));
        return $result;
    }
}
