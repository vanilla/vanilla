<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Asset;

use Vanilla\Layout\LayoutAssetAwareInterface;
use Vanilla\Layout\LayoutAssetAwareTrait;
use Vanilla\Web\PageHeadAwareInterface;
use Vanilla\Web\PageHeadAwareTrait;
use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\DefaultSectionTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * React widget that is an asset in a layout.
 *
 * Assets may have restrictions on where they can be placed and in what views.
 */
abstract class AbstractLayoutAsset implements
    ReactWidgetInterface,
    CombinedPropsWidgetInterface,
    PageHeadAwareInterface,
    LayoutAssetAwareInterface
{
    use CombinedPropsWidgetTrait;
    use LayoutAssetAwareTrait;
    use PageHeadAwareTrait;
    use DefaultSectionTrait;
}
