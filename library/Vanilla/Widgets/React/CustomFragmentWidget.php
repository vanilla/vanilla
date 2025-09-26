<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Garden\Schema\Schema;
use Vanilla\Dashboard\Models\FragmentModel;
use Vanilla\Layout\Resolvers\CustomFragmentResolver;

/**
 * Widget for custom fragments. For usage with {@link CustomFragmentResolver}.
 *
 * Instead of having a PHP declared widget, the widget definition is based off of a row in GDN_fragment.
 *
 * @see FragmentModel
 */
class CustomFragmentWidget extends ReactWidget
{
    use AllSectionTrait;

    /** @var array Fragment row from GDN_fragment */
    protected array $fragmentRow = [];

    /**
     * @param array $fragmentRow
     */
    public function setFragmentRow(array $fragmentRow): void
    {
        $this->fragmentRow = $fragmentRow;
    }

    /**
     * @inheritdoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        // Custom no HTML for it.
        return "";
    }

    /**
     * @inheritdoc
     */
    public function getProps(): ?array
    {
        $props = $this->props;

        // Add on fragment info
        $props["fragmentImpl"] = $this->fragmentRow;
        return $props;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "CustomFragmentWidget";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetIconPath(): ?string
    {
        return "/applications/dashboard/design/images/widgetIcons/customhtml.svg";
    }

    /**
     * This is a custom fragment and we don't know the schema statically.
     *
     * It does get joined into the catalog through {@link CustomFragmentResolver} though.
     *
     * @return Schema
     */
    public static function getWidgetSchema(): Schema
    {
        return new Schema(["type" => "object"]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "Custom Fragment";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Custom";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "custom-fragment";
    }
}
