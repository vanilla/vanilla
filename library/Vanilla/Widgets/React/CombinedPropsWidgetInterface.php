<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

/**
 * Interface for something that can tag a group of props.
 */
interface CombinedPropsWidgetInterface
{
    /**
     * Set the props of a react component.
     *
     * @param array<string, mixed> $props The props
     * @param bool $merge Whether or not we should merge with existing props.
     */
    public function setProps(array $props, bool $merge = false);
}
