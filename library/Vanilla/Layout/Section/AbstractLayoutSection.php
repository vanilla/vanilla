<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Section;

use Vanilla\Web\TwigRenderTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * Interface representing a layout section.
 */
abstract class AbstractLayoutSection implements ReactWidgetInterface, CombinedPropsWidgetInterface
{
    use CombinedPropsWidgetTrait;
    use TwigRenderTrait;

    /**
     * Render out an array of reach children as SEO HTML.
     * @param array<array{"reactComponent": string, "$reactProps": array, "$seoContent"?: string}> $children The array of react children
     * @return string
     */
    protected function renderSectionChildrenHtml(array $children): string
    {
        $result = "";
        foreach ($children as $child) {
            $seoContent = $child['$seoContent'] ?? "Child Item";
            if (!$seoContent) {
                continue;
            }

            $result .= "<div class='sectionItem'>{$seoContent}</div>";
        }
        if (!empty($result)) {
            $result = "<div class='seoSectionPiece'>{$result}</div>";
        }
        return $result;
    }

    /**
     * @return string
     */
    public static function getWidgetIconPath(): string
    {
        return "";
    }

    /**
     * We don't care about this here.
     *
     * @return array
     */
    public static function getAllowedSectionIDs(): array
    {
        return [];
    }
}
