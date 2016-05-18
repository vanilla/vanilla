<?php
class DashboardNavAdapterModule extends DashboardNavModule {

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
        $key = slugify($group) . '.' . slugify($text);
        $cssClass = val('class', $attributes, '');
        if ($permission != false && !$this->isAllowed($permission)) {
            return $this;
        }
        parent::addLink($text, $url, $key, $cssClass, [], $attributes);
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
    public function addItem($group, $text, $permission = false, $attributes = array()) {

        $itemTypes = ['divider', 'link', 'group'];

        if (in_array($group, $itemTypes)) {
            parent::addItem($group, $text);
        } else {
            if ($permission != false && !$this->isAllowed($permission)) {
                return $this;
            }
            parent::addGroup($text, slugify($group), val('class', $attributes), '', $attributes);
        }
        return $this;
    }

    public function clearGroups() {
    }

    /**
     * @param string $route
     */
    public function highlightRoute($route) {
        $this->setHighlightRoute($route);
    }

    /**
     *
     *
     * @param $Group
     * @param $Text
     */
    public function removeLink($Group, $Text) {
    }

    /**
     * Removes all links from a specific group.
     */
    public function removeLinks($Group) {
    }

    /**
     * Removes an entire group of links, and the group itself, from the menu.
     */
    public function removeGroup($Group) {
    }
}
