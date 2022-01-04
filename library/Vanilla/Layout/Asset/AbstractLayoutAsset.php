<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\Asset;

use Vanilla\Widgets\React\CombinedPropsWidgetInterface;
use Vanilla\Widgets\React\CombinedPropsWidgetTrait;
use Vanilla\Widgets\React\ReactWidgetInterface;

/**
 * React widget that is an asset in a layout.
 *
 * Assets may have restrictions on where they can be placed and in what views.
 */
abstract class AbstractLayoutAsset implements ReactWidgetInterface, CombinedPropsWidgetInterface {

    use CombinedPropsWidgetTrait;
}
