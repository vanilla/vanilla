<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Section;

use Garden\Schema\Schema;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Widgets\Schema\ReactChildrenSchema;

/**
 * Widget representing a 3 column layout with evenly spaced columns.
 */
class SectionThreeColumnsEven extends AbstractLayoutSection implements HydrateAwareInterface
{
    use HydrateAwareTrait;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->addChildComponentName("SectionEvenColumns");
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SectionThreeColumnsEven";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "left?" => new ReactChildrenSchema(),
            "middle?" => new ReactChildrenSchema(),
            "right?" => new ReactChildrenSchema(),
            "breadcrumbs?" => new ReactChildrenSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "3 Columns - Even";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "section.3-columns-even";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/sectionIcons/3column-even.svg";
    }

    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $middle = $this->renderSectionChildrenHtml($props["middle"] ?? []);
        $left = $this->renderSectionChildrenHtml($props["left"] ?? []);
        $right = $this->renderSectionChildrenHtml($props["right"] ?? []);
        $breadcrumbs = $this->renderSectionChildrenHtml($props["breadcrumbs"] ?? []);
        $tpl = <<<TWIG
<section>
<div class="seoSectionRow seoBreadcrumbs">
    {{- breadcrumbs|raw -}}
</div>
<div class="seoSectionColumn">
    {{- left|raw -}}
</div>
<div class="seoSectionColumn">
    {{- middle|raw -}}
</div>
<div class="seoSectionColumn">
    {{- right|raw -}}
</div>
</section>
TWIG;

        $result = $this->renderTwigFromString($tpl, [
            "middle" => $middle,
            "left" => $left,
            "right" => $right,
            "breadcrumbs" => $breadcrumbs,
        ]);
        return $result;
    }
}
