<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;

/**
 * Class CallToActionWidget
 */
class CallToActionWidget extends AbstractReactModule implements CombinedPropsWidgetInterface
{
    use CombinedPropsWidgetTrait;
    use HomeWidgetContainerSchemaTrait;
    use WidgetSchemaTrait;
    use CallToActionWidgetTrait;

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
}
