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
trait LayoutAssetAwareTrait
{
    /** @var bool */
    public $getAsset;

    /** @var array */
    public $widgetList;

    /**
     * Set partialAsset for data population.
     *
     * @param bool $getAsset
     */
    public function setPartialHydrate(bool $getAsset)
    {
        $this->getAsset = $getAsset;
    }

    /**
     * Add widget name.
     *
     * @param string $widget
     */
    public function addWidgetName(string $widget)
    {
        if ($this->widgetList == null) {
            $this->widgetList = [];
        }
        $this->widgetList[] = $widget;
    }

    /**
     * Get widget name.
     *
     * @return array
     */
    public function getWidgetNames()
    {
        return $this->widgetList;
    }
}
