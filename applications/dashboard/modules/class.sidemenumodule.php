<?php
/**
 * Side menu module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

if (!class_exists('SideMenuModule', false)) {
    /**
     * Manages the items in the page menu and eventually returns the menu as a string with toString();
     */
    class SideMenuModule extends Gdn_Module {

        /** @var bool Should the group titles be autolinked to the first anchor in the group? Default TRUE. */
        public $AutoLinkGroups;

        /** @var string|bool */
        public $EventName = false;

        /** @var array An array of menu items. */
        public $Items;

        /** @var array */
        protected $_Items;

        /** @var string The html id attribute to be applied to the root element of the menu. Default is "Menu". */
        public $HtmlId;

        /** @var string The class attribute to be applied to the root element of the breadcrumb. Default is none. */
        public $CssClass;

        /** @var array An array of menu group names arranged in the order that the menu should be rendered. */
        public $Sort;

        /**
         * @var string A route that, if found in the menu links, should cause that link to
         * have the Highlight class applied. This property is assigned with $this->highlight();
         */
        private $_HighlightRoute;

        /**
         *
         *
         * @param string $sender
         */
        public function __construct($sender = '') {
            parent::__construct($sender);

            $this->_ApplicationFolder = 'dashboard';
            $this->HtmlId = 'SideMenu';
            $this->AutoLinkGroups = true;
            $this->clearGroups();
        }

        /**
         *
         *
         * @param $group
         * @param $text
         * @param $url
         * @param bool $permission
         * @param array $attributes
         */
        public function addLink($group, $text, $url, $permission = false, $attributes = []) {
            if (!array_key_exists($group, $this->Items)) {
                $this->addItem($group, t($group));
            }
            if ($text === false) {
                // This link is the group heading.
                $this->Items[$group]['Url'] = $url;
                $this->Items[$group]['Permission'] = $permission;
                $this->Items[$group]['Attributes'] = array_merge($this->Items[$group]['Attributes'], $attributes);
            } else {
                $link = ['Text' => $text, 'Url' => $url, 'Permission' => $permission, 'Attributes' => $attributes, '_Sort' => count($this->Items[$group]['Links'])];
                if (isset($attributes['After'])) {
                    $link['After'] = $attributes['After'];
                    unset($attributes['After']);
                }
                $this->Items[$group]['Links'][$url] = $link;
            }
        }

        /**
         *
         *
         * @param $group
         * @param $text
         * @param bool $permission
         * @param array $attributes
         */
        public function addItem($group, $text, $permission = false, $attributes = []) {
            if (!array_key_exists($group, $this->Items)) {
                $item = ['Group' => $group, 'Links' => [], 'Attributes' => [], '_Sort' => count($this->Items)];
            } else {
                $item = $this->Items[$group];
            }


            if (isset($attributes['After'])) {
                $item['After'] = $attributes['After'];
                unset($attributes['After']);
            }

            $item['Text'] = $text;
            $item['Permission'] = $permission;
            $item['Attributes'] = array_merge($item['Attributes'], $attributes);

            $this->Items[$group] = $item;
        }

        /**
         *
         *
         * @return string
         */
        public function assetTarget() {
            return 'Menu';
        }

        /**
         *
         */
        public function checkPermissions() {
            $session = Gdn::session();

            foreach ($this->Items as $group => $item) {
                if (val('Permission', $item) && !$session->checkPermission($item['Permission'], false)) {
                    unset($this->Items[$group]);
                    continue;
                }

                foreach ($item['Links'] as $key => $link) {
                    if (val('Permission', $link) && !$session->checkPermission($link['Permission'], false)) {
                        unset($this->Items[$group]['Links'][$key]);
                    }
                }
                // Remove the item if there are no more links.
                if (!val('Url', $item) && !count($this->Items[$group]['Links'])) {
                    unset($this->Items[$group]);
                }
            }
        }

        /**
         *
         */
        public function clearGroups() {
            $this->Items = [];
        }

        /**
         *
         *
         * @param $a
         * @param null $b
         * @return int|void
         */
        protected function _Compare($a, $b = null) {
            static $groups;
            if ($b === null) {
                $groups = $a;
                return;
            }

            $sortA = $this->_CompareSort($a, $groups);
            $sortB = $this->_CompareSort($b, $groups);

            if ($sortA > $sortB) {
                return 1;
            } elseif ($sortA < $sortB)
                return -1;
            elseif ($a['_Sort'] > $b['_Sort']) // fall back to order added
                return 1;
            elseif ($a['_Sort'] < $b['_Sort'])
                return -1;
            else {
                return 0;
            }
        }

        /**
         * The sort is determined by looking at:
         *  a) The item's sort.
         *  b) Whether the item is after another.
         *  c) The order the item was added.
         * @param array $a
         * @param array $all
         * @return int
         */
        protected function _CompareSort($a, $all) {
            if (isset($a['Sort'])) {
                return $a['Sort'];
            }
            if (isset($a['After']) && isset($all[$a['After']])) {
                $after = $all[$a['After']];
                if (isset($after['Sort'])) {
                    return $after['Sort'] + 0.1;
                }
                return $after['_Sort'] + 0.1;
            }

            return $a['_Sort'];
        }

        /**
         *
         *
         * @param $route
         */
        public function highlightRoute($route) {
            $this->_HighlightRoute = $route;
        }

        /**
         *
         *
         * @param $group
         * @param $text
         */
        public function removeLink($group, $text) {
            if (array_key_exists($group, $this->Items) && isset($this->Items[$group]['Links'])) {
                $links =& $this->Items[$group]['Links'];

                if (isset($links[$text])) {
                    unset($this->Items[$group]['Links'][$text]);
                    return;
                }


                foreach ($links as $index => $link) {
                    if (val('Text', $link) == $text) {
                        unset($this->Items[$group]['Links'][$index]);
                        return;
                    }
                }
            }
        }

        /**
         * Removes all links from a specific group.
         */
        public function removeLinks($group) {
            $this->Items[$group] = [];
        }

        /**
         * Removes an entire group of links, and the group itself, from the menu.
         */
        public function removeGroup($group) {
            if (array_key_exists($group, $this->Items)) {
                unset($this->Items[$group]);
            }
        }

        /**
         * Render the menu.
         *
         * @param string $highlightRoute
         * @return string
         * @throws Exception
         */
        public function toString($highlightRoute = '') {
            if ($highlightRoute == '') {
                $highlightRoute = $this->_HighlightRoute;
            }
            if ($highlightRoute == '') {
                $highlightRoute = Gdn_Url::request();
            }
            $highlightUrl = url($highlightRoute);

            // Apply a sort to the items if given.
            if (is_array($this->Sort)) {
                $sort = array_flip($this->Sort);
                foreach ($this->Items as $group => &$item) {
                    if (isset($sort[$group])) {
                        $item['Sort'] = $sort[$group];
                    } else {
                        $item['_Sort'] += count($sort);
                    }

                    foreach ($item['Links'] as $url => &$link) {
                        if (isset($sort[$url])) {
                            $link['Sort'] = $sort[$url];
                        } elseif (isset($sort[$link['Text']]))
                            $link['Sort'] = $sort[$link['Text']];
                        else {
                            $link['_Sort'] += count($sort);
                        }
                    }
                }
            }

            // Sort the groups.
            $this->_Compare($this->Items);
            uasort($this->Items, [$this, '_Compare']);

            // Sort the items within the groups.
            foreach ($this->Items as &$item) {
                $this->_Compare($item['Links']);
                uasort($item['Links'], [$this, '_Compare']);

                // Highlight the group.
                if (val('Url', $item) && url($item['Url']) == $highlightUrl) {
                    $item['Attributes']['class'] = concatSep(' ', val('class', $item['Attributes']), 'Active');
                }

                // Hightlight the correct item in the group.
                foreach ($item['Links'] as &$link) {
                    if (val('Url', $link) && url($link['Url']) == $highlightUrl) {
                        $link['Attributes']['class'] = concatSep(' ', val('class', $link['Attributes']), 'Active');
                        $item['Attributes']['class'] = concatSep(' ', val('class', $item['Attributes']), 'Active');
                    }
                }
            }

            return parent::toString();
        }
    }
}
