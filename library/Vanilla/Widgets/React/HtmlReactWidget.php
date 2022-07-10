<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Knowledge\Models\ArticleJsonLD;
use Vanilla\Knowledge\Models\SearchJsonLD;

/**
 * Widget for rendering raw HTML in react.
 */
class HtmlReactWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface {

    use CombinedPropsWidgetTrait;

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string {
        return 'HtmlWidget';
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string {
        return "/applications/dashboard/design/images/widgetIcons/customhtml.svg";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema {
        return Schema::parse([
            'html:s' => [
                'description' => 'Sanitized HTML to render.'
            ],
            "isAdvertisement:b" => [
                "description" => "Controls if element is advertisement, and display is controlled by permissions.",
                "default" => false
            ]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array {
        if ($this->props['isAdvertisement'] && checkPermission('noAds.use')) {
            return null;
        }
        return $this->props;
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string {
        return 'Custom HTML';
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string {
        return 'html';
    }
}
