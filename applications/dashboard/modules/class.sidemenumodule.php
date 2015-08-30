<?php
/**
 * Side menu module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

if (!class_exists('SideMenuModule', false)) {
    /**
     * Manages the items in the page menu and eventually returns the menu as a string with ToString();
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
         * have the Highlight class applied. This property is assigned with $this->Highlight();
         */
        private $_HighlightRoute;

        /**
         *
         *
         * @param string $Sender
         */
        public function __construct($Sender = '') {
            parent::__construct($Sender);

            $this->_ApplicationFolder = 'dashboard';
            $this->HtmlId = 'SideMenu';
            $this->AutoLinkGroups = true;
            $this->ClearGroups();
        }

        /**
         *
         *
         * @param $Group
         * @param $Text
         * @param $Url
         * @param bool $Permission
         * @param array $Attributes
         */
        public function addLink($Group, $Text, $Url, $Permission = false, $Attributes = array()) {
            if (!array_key_exists($Group, $this->Items)) {
                $this->AddItem($Group, t($Group));
            }
            if ($Text === false) {
                // This link is the group heading.
                $this->Items[$Group]['Url'] = $Url;
                $this->Items[$Group]['Permission'] = $Permission;
                $this->Items[$Group]['Attributes'] = array_merge($this->Items[$Group]['Attributes'], $Attributes);
            } else {
                $Link = array('Text' => $Text, 'Url' => $Url, 'Permission' => $Permission, 'Attributes' => $Attributes, '_Sort' => count($this->Items[$Group]['Links']));
                if (isset($Attributes['After'])) {
                    $Link['After'] = $Attributes['After'];
                    unset($Attributes['After']);
                }
                $this->Items[$Group]['Links'][$Url] = $Link;
            }
        }

        /**
         *
         *
         * @param $Group
         * @param $Text
         * @param bool $Permission
         * @param array $Attributes
         */
        public function addItem($Group, $Text, $Permission = false, $Attributes = array()) {
            if (!array_key_exists($Group, $this->Items)) {
                $Item = array('Group' => $Group, 'Links' => array(), 'Attributes' => array(), '_Sort' => count($this->Items));
            } else {
                $Item = $this->Items[$Group];
            }


            if (isset($Attributes['After'])) {
                $Item['After'] = $Attributes['After'];
                unset($Attributes['After']);
            }

            $Item['Text'] = $Text;
            $Item['Permission'] = $Permission;
            $Item['Attributes'] = array_merge($Item['Attributes'], $Attributes);

            $this->Items[$Group] = $Item;
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
            $Session = Gdn::session();

            foreach ($this->Items as $Group => $Item) {
                if (val('Permission', $Item) && !$Session->checkPermission($Item['Permission'], false)) {
                    unset($this->Items[$Group]);
                    continue;
                }

                foreach ($Item['Links'] as $Key => $Link) {
                    if (val('Permission', $Link) && !$Session->checkPermission($Link['Permission'], false)) {
                        unset($this->Items[$Group]['Links'][$Key]);
                    }
                }
                // Remove the item if there are no more links.
                if (!val('Url', $Item) && !count($this->Items[$Group]['Links'])) {
                    unset($this->Items[$Group]);
                }
            }
        }

        /**
         *
         */
        public function clearGroups() {
            $this->Items = array();
        }

        /**
         *
         *
         * @param $A
         * @param null $B
         * @return int|void
         */
        protected function _Compare($A, $B = null) {
            static $Groups;
            if ($B === null) {
                $Groups = $A;
                return;
            }

            $SortA = $this->_CompareSort($A, $Groups);
            $SortB = $this->_CompareSort($B, $Groups);

            if ($SortA > $SortB) {
                return 1;
            } elseif ($SortA < $SortB)
                return -1;
            elseif ($A['_Sort'] > $B['_Sort']) // fall back to order added
                return 1;
            elseif ($A['_Sort'] < $B['_Sort'])
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
         * @param array $A
         * @param array $All
         * @return int
         */
        protected function _CompareSort($A, $All) {
            if (isset($A['Sort'])) {
                return $A['Sort'];
            }
            if (isset($A['After']) && isset($All[$A['After']])) {
                $After = $All[$A['After']];
                if (isset($After['Sort'])) {
                    return $After['Sort'] + 0.1;
                }
                return $After['_Sort'] + 0.1;
            }

            return $A['_Sort'];
        }

        /**
         *
         *
         * @param $Route
         */
        public function highlightRoute($Route) {
            $this->_HighlightRoute = $Route;
        }

        /**
         *
         *
         * @param $Group
         * @param $Text
         */
        public function removeLink($Group, $Text) {
            if (array_key_exists($Group, $this->Items) && isset($this->Items[$Group]['Links'])) {
                $Links =& $this->Items[$Group]['Links'];

                if (isset($Links[$Text])) {
                    unset($this->Items[$Group]['Links'][$Text]);
                    return;
                }


                foreach ($Links as $Index => $Link) {
                    if (val('Text', $Link) == $Text) {
                        unset($this->Items[$Group]['Links'][$Index]);
                        return;
                    }
                }
            }
        }

        /**
         * Removes all links from a specific group.
         */
        public function removeLinks($Group) {
            $this->Items[$Group] = array();
        }

        /**
         * Removes an entire group of links, and the group itself, from the menu.
         */
        public function removeGroup($Group) {
            if (array_key_exists($Group, $this->Items)) {
                unset($this->Items[$Group]);
            }
        }

        /**
         *
         *
         * @param string $HighlightRoute
         * @return string
         * @throws Exception
         */
        public function toString($HighlightRoute = '') {
            Gdn::controller()->EventArguments['SideMenu'] = $this;
            if ($this->EventName) {
                Gdn::controller()->fireEvent($this->EventName);
            }


            if ($HighlightRoute == '') {
                $HighlightRoute = $this->_HighlightRoute;
            }
            if ($HighlightRoute == '') {
                $HighlightRoute = Gdn_Url::Request();
            }
            $HighlightUrl = url($HighlightRoute);

            // Apply a sort to the items if given.
            if (is_array($this->Sort)) {
                $Sort = array_flip($this->Sort);
                foreach ($this->Items as $Group => &$Item) {
                    if (isset($Sort[$Group])) {
                        $Item['Sort'] = $Sort[$Group];
                    } else {
                        $Item['_Sort'] += count($Sort);
                    }

                    foreach ($Item['Links'] as $Url => &$Link) {
                        if (isset($Sort[$Url])) {
                            $Link['Sort'] = $Sort[$Url];
                        } elseif (isset($Sort[$Link['Text']]))
                            $Link['Sort'] = $Sort[$Link['Text']];
                        else {
                            $Link['_Sort'] += count($Sort);
                        }
                    }
                }
            }

            // Sort the groups.
            $this->_Compare($this->Items);
            uasort($this->Items, array($this, '_Compare'));

            // Sort the items within the groups.
            foreach ($this->Items as &$Item) {
                $this->_Compare($Item['Links']);
                uasort($Item['Links'], array($this, '_Compare'));

                // Highlight the group.
                if (val('Url', $Item) && url($Item['Url']) == $HighlightUrl) {
                    $Item['Attributes']['class'] = ConcatSep(' ', val('class', $Item['Attributes']), 'Active');
                }

                // Hightlight the correct item in the group.
                foreach ($Item['Links'] as &$Link) {
                    if (val('Url', $Link) && url($Link['Url']) == $HighlightUrl) {
                        $Link['Attributes']['class'] = ConcatSep(' ', val('class', $Link['Attributes']), 'Active');
                        $Item['Attributes']['class'] = ConcatSep(' ', val('class', $Item['Attributes']), 'Active');
                    }
                }
            }

            return parent::ToString();
        }
    }
}
