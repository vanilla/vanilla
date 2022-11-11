<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Widgets;

use Gdn;
use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\JsInterpop\AbstractReactModule;
use Vanilla\Widgets\HomeWidgetContainerSchemaTrait;
use Vanilla\Widgets\WidgetSchemaTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;

/**
 * Class GuestCallToActionWidget
 */
class GuestCallToActionWidget extends AbstractReactModule implements CombinedPropsWidgetInterface
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
        return "guest-cta";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Guest Sign In";
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "GuestCallToActionWidget";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/widgetIcons/guest-cta.svg";
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

        //only for guests
        if (Gdn::session()->isValid()) {
            return null;
        }

        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            self::widgetTitleSchema(null, true, t("Welcome!")),
            self::widgetDescriptionSchema(
                null,
                false,
                t("It looks like you're new here. Sign in or register to get started.")
            ),
            self::getWidgetSpecificSchema(true)
        );
    }
}
