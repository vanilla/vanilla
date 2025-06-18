<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Vanilla\InjectableInterface;
use Vanilla\Layout\HydrateAwareInterface;
use Vanilla\Layout\HydrateAwareTrait;
use Vanilla\Web\TwigRenderTrait;

/**
 * Abstract class for a widget that will have its view rendered by the React JS framework.
 */
abstract class ReactWidget implements ReactWidgetInterface, CombinedPropsWidgetInterface, HydrateAwareInterface
{
    use CombinedPropsWidgetTrait;
    use HydrateAwareTrait;
    use TwigRenderTrait;
    use DefaultSectionTrait;

    /**
     * @inheritdoc
     */
    public static function getFragmentClasses(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getWidgetGroup(): string
    {
        return "Widgets";
    }
}
