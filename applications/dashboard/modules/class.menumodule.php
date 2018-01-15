<?php
/**
 * Menu module.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

if (!class_exists('MenuModule', false)) {
    /**
     * Manages the items in the page menu and eventually returns the menu as a
     * string with toString();
     */
    class MenuModule extends Gdn_Module {

        /**  @var array Menu items. */
        public $Items;

        /** @var string The html id attribute to be applied to the root element of the menu.vDefault is "Menu". */
        public $HtmlId;

        /** @var string The class attribute to be applied to the root element of the breadcrumb. Default is none. */
        public $CssClass;

        /** @var array Menu group names arranged in the order that the menu should be rendered. */
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
            $this->HtmlId = 'Menu';
            $this->clearGroups();
            parent::__construct($sender);
        }

        /**
         *
         *
         * @param $group
         * @param $text
         * @param $url
         * @param bool $permission
         * @param string $attributes
         * @param string $anchorAttributes
         */
        public function addLink($group, $text, $url, $permission = false, $attributes = '', $anchorAttributes = '') {
            if (!array_key_exists($group, $this->Items)) {
                $this->Items[$group] = [];
            }

            $this->Items[$group][] = ['Text' => $text, 'Url' => $url, 'Permission' => $permission, 'Attributes' => $attributes, 'AnchorAttributes' => $anchorAttributes];
        }

        /**
         *
         *
         * @param $group
         * @param $text
         * @param bool $permission
         * @param string $attributes
         */
        public function addItem($group, $text, $permission = false, $attributes = '') {
            if (!array_key_exists($group, $this->Items)) {
                $this->Items[$group] = [];
            }

            $this->Items[$group][] = ['Text' => $text, 'Url' => false, 'Permission' => $permission, 'Attributes' => $attributes];
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
        public function clearGroups() {
            $this->Items = [];
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
            if (array_key_exists($group, $this->Items) && is_array($this->Items[$group])) {
                foreach ($this->Items[$group] as $index => $groupArray) {
                    if ($this->Items[$group][$index]['Text'] == $text) {
                        unset($this->Items[$group][$index]);
                        array_merge($this->Items[$group]);
                        break;
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
         *
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

            $this->fireEvent('BeforeToString');

            $username = '';
            $userID = '';
            $session_TransientKey = '';
            $session = Gdn::session();
            $admin = false;
            if ($session->isValid() === true) {
                $userID = $session->User->UserID;
                $username = $session->User->Name;
                $session_TransientKey = $session->transientKey();
                $admin = $session->User->Admin > 0 ? true : false;
            }

            $menu = '';
            if (count($this->Items) > 0) {
                // Apply the menu group sort if present...
                if (is_array($this->Sort)) {
                    $items = [];
                    $count = count($this->Sort);
                    for ($i = 0; $i < $count; ++$i) {
                        $group = $this->Sort[$i];
                        if (array_key_exists($group, $this->Items)) {
                            $items[$group] = $this->Items[$group];
                            unset($this->Items[$group]);
                        }
                    }
                    foreach ($this->Items as $group => $links) {
                        $items[$group] = $links;
                    }
                } else {
                    $items = $this->Items;
                }
                foreach ($items as $groupName => $links) {
                    $itemCount = 0;
                    $linkCount = 0;
                    $openGroup = false;
                    $group = '';
                    foreach ($links as $key => $link) {
                        $currentLink = false;
                        $showLink = false;
                        $requiredPermissions = array_key_exists('Permission', $link) ? $link['Permission'] : false;
                        if ($requiredPermissions !== false && !is_array($requiredPermissions)) {
                            $requiredPermissions = explode(',', $requiredPermissions);
                        }

                        // Show if there are no permissions or the user has ANY of the specified permissions or the user is admin
                        $showLink = $admin || $requiredPermissions === false || Gdn::session()->checkPermission($requiredPermissions, false);

                        if ($showLink === true) {
                            if ($itemCount == 1) {
                                $group .= '<ul>';
                                $openGroup = true;
                            } elseif ($itemCount > 1) {
                                $group .= "</li>\r\n";
                            }

                            $url = val('Url', $link);
                            if (substr($link['Text'], 0, 1) === '\\') {
                                $text = substr($link['Text'], 1);
                            } else {
                                $text = str_replace('{Username}', $username, $link['Text']);
                            }
                            $attributes = val('Attributes', $link, []);
                            $anchorAttributes = val('AnchorAttributes', $link, []);
                            if ($url !== false) {
                                $url = url(str_replace(['{Username}', '{UserID}', '{Session_TransientKey}'], [urlencode($username), $userID, $session_TransientKey], $link['Url']));
                                $currentLink = $url == url($highlightRoute);

                                $cssClass = val('class', $attributes, '');
                                if ($currentLink) {
                                    $attributes['class'] = $cssClass.' Highlight';
                                }

                                $group .= '<li'.attribute($attributes).'><a'.attribute($anchorAttributes).' href="'.$url.'">'.$text.'</a>';
                                ++$linkCount;
                            } else {
                                $group .= '<li'.attribute($attributes).'>'.$text;
                            }
                            ++$itemCount;
                        }
                    }
                    if ($openGroup === true) {
                        $group .= "</li>\r\n</ul>\r\n";
                    }

                    if ($group != '' && $linkCount > 0) {
                        $menu .= $group."</li>\r\n";
                    }
                }
                if ($menu != '') {
                    $menu = '<ul id="'.$this->HtmlId.'"'.($this->CssClass != '' ? ' class="'.$this->CssClass.'"' : '').'>'.$menu.'</ul>';
                }
            }
            return $menu;
        }
    }
}
