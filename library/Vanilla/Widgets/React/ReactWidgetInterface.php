<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Widgets\React;

use Vanilla\Contracts\Addons\WidgetInterface;

/**
 * Interface for a widget that will have its view rendered by the React JS framework.
 */
interface ReactWidgetInterface extends WidgetInterface
{
    /**
     * Get props for react component.
     *
     * @return array|null If null is returned the component will not be rendered.
     */
    public function getProps(): ?array;

    /**
     * Render HTML content for SEO for the widget.
     *
     * @param array $props The props from getProps().
     *
     * @return string|null
     */
    public function renderSeoHtml(array $props): ?string;

    /**
     * Get react component name.
     *
     * @return string
     */
    public static function getComponentName(): string;

    /**
     * Get widget icon url.
     */
    public static function getWidgetIconPath(): ?string;

    /**
     * Say what sections this widget can be placed in.
     *
     */
    public static function getAllowedSectionIDs(): array;

    /**
     * Get the fragment types this widget uses.
     *
     * @return array<class-string<FragmentMeta>>
     */
    public static function getFragmentClasses(): array;

    /**
     * Get a group to place the widget in.
     *
     * @return string
     */
    public static function getWidgetGroup(): string;
}
