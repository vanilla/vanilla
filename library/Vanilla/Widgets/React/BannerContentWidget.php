<?php
/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Widgets\React\BannerFullWidget as ReactBannerFullWidget;
use Gdn_Upload;

/**
 * Class BannerContentWidget
 */
class BannerContentWidget extends ReactBannerFullWidget {

    /**
     * @inheritDoc
     */
    public static function getWidgetName(): string {
        return "Content Banner";
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetID(): string {
        return "banner.content";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string {
        return "/applications/dashboard/design/images/widgetIcons/contentbanner.svg";
    }

    /**
     * @inheritDoc
     */
    public function getProps(): ?array {
        $parentProps = parent::getProps();
        $categoryID = $this->props['categoryID'] ?? null;
        $iconUrl = $this->categoryModel->getCategoryFieldRecursive($categoryID, 'Photo', null);
        $iconUrl = $iconUrl ? Gdn_Upload::url($iconUrl) : null;
        $props = array_merge($parentProps, [
            'isContentBanner' => true,
            'iconImage' => $iconUrl,
        ], $this->props);
        return $props;
    }

    /**
     * @inheritDoc
     */
    public static function getWidgetSchema(): Schema {
        return SchemaUtils::composeSchemas(
            parent::getWidgetSchema(),
            Schema::parse([
                'iconImage:s?' => 'URL for the icon image.'
            ])
        );
    }
}
