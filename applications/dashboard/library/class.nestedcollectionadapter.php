<?php

/**
 * Adapter methods for nav modules using the NestedCollection trait.
 * Maintains backwards compatability with the SideMenuModule.
 *
 * @author Becky Van Bussel <becky@vanillaforums.com>
 * @copyright 2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @since 2.3
 */

class NestedCollectionAdapter {

    public $siteNavModule;

    public function __construct($siteNavModule = null) {
        if ($siteNavModule) {
            $this->siteNavModule = $siteNavModule;
        } else {
            $this->siteNavModule =  new SiteNavModule();
        }
    }

    /**
     *
     *
     * @param $group
     * @param $text
     * @param $url
     * @param bool $permission
     * @param array $attributes
     * @return $this|object
     */
    public function addLink($group, $text, $url, $permission = false, $attributes = []) {
        if ($permission === false) {
            $permission = true;
        }
        $key = NavModule::textToKey($group.'.'.$text);
        $cssClass = val('class', $attributes, '');
        $this->siteNavModule->addLinkIf($permission, $text, $url, $key, $cssClass, [], $attributes);
        return $this;
    }

    /**
     *
     *
     * @param $group
     * @param $text
     * @param bool $permission
     * @param array $attributes
     * @return $this|void
     */
    public function addItem($group, $text, $permission = false, $attributes = []) {
        if ($permission === false) {
            $permission = true;
        }
        $this->siteNavModule->addGroupIf($permission, $text, NavModule::textToKey($group), val('class', $attributes), '', $attributes);
        return $this;
    }

    public function clearGroups() {
    }

    /**
     * @param string $route
     */
    public function highlightRoute($route) {
        $this->siteNavModule->setHighlightRoute($route);
    }

    /**
     *
     *
     * @param $group
     * @param $text
     */
    public function removeLink($group, $text) {
    }

    /**
     * Removes all links from a specific group.
     */
    public function removeLinks($group) {
    }

    /**
     * Removes an entire group of links, and the group itself, from the menu.
     */
    public function removeGroup($group) {
    }
}
