<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Higher Logic.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

/**
 * Helper class to get Widget list.
 */
interface LayoutAssetAwareInterface
{
    /**
     * Set partialAsset for data population.
     *
     * @param bool $getAsset
     */
    public function setPartialHydrate(bool $getAsset);

    /**
     * Add widget name.
     *
     * @param string $widget
     */
    public function addWidgetName(string $widget);

    /**
     * Return widget names.
     *
     * @return string
     */
    public function getWidgetNames();
}
