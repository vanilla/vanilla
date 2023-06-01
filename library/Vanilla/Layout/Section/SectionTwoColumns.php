<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Section;

use Garden\Schema\Schema;
use Vanilla\Web\TwigStaticRenderer;
use Vanilla\Widgets\Schema\ReactChildrenSchema;

/**
 * Widget representing a 2 column layout.
 */
class SectionTwoColumns extends AbstractLayoutSection
{
    /**
     * @inheritDoc
     */
    public function renderSeoHtml(array $props): ?string
    {
        $mainTop = $this->renderSectionChildrenHtml($props["mainTop"] ?? []);
        $mainBottom = $this->renderSectionChildrenHtml($props["mainBottom"] ?? []);
        $secondaryTop = $this->renderSectionChildrenHtml($props["secondaryTop"] ?? []);
        $secondaryBottom = $this->renderSectionChildrenHtml($props["secondaryBottom"] ?? []);
        $breadcrumbs = $this->renderSectionChildrenHtml($props["breadcrumbs"] ?? []);
        $tpl = <<<TWIG
<section>
{{- breadcrumbs|raw -}}
<div class="seoSectionColumn mainColumn">
    {{- mainTop|raw -}}
    {{- mainBottom|raw -}}
</div>
<div class="seoSectionColumn">
    {{- secondaryTop|raw -}}
    {{- secondaryBottom|raw -}}
</div>
</section>
TWIG;

        $result = $this->renderTwigFromString($tpl, [
            "mainTop" => $mainTop,
            "mainBottom" => $mainBottom,
            "secondaryTop" => $secondaryTop,
            "secondaryBottom" => $secondaryBottom,
            "breadcrumbs" => $breadcrumbs,
        ]);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public static function getComponentName(): string
    {
        return "SectionTwoColumns";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetSchema(): Schema
    {
        return Schema::parse([
            "isInverted?" => [
                "type" => "boolean",
                "default" => false,
                "description" => 'If "true", places the secondary column to the left.',
            ],
            "mainTop?" => new ReactChildrenSchema(),
            "mainBottom?" => new ReactChildrenSchema(),
            "secondaryTop?" => new ReactChildrenSchema(),
            "secondaryBottom?" => new ReactChildrenSchema(),
            "breadcrumbs?" => new ReactChildrenSchema(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetName(): string
    {
        return "2 Columns";
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetID(): string
    {
        return "section.2-columns";
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "/applications/dashboard/design/images/sectionIcons/2columnu.svg";
    }
}
