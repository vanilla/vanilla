<?php
/**
 * Menu module.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

if (!class_exists('MenuModule', false)) {
    /**
     * Manages the items in the page menu and eventually returns the menu as a
     * string with ToString();
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
         * have the Highlight class applied. This property is assigned with $this->Highlight();
         */
        private $_HighlightRoute;

        /**
         *
         *
         * @param string $Sender
         */
        public function __construct($Sender = '') {
            $this->HtmlId = 'Menu';
            $this->ClearGroups();
            parent::__construct($Sender);
        }

        /**
         *
         *
         * @param $Group
         * @param $Text
         * @param $Url
         * @param bool $Permission
         * @param string $Attributes
         * @param string $AnchorAttributes
         */
        public function addLink($Group, $Text, $Url, $Permission = false, $Attributes = '', $AnchorAttributes = '') {
            if (!array_key_exists($Group, $this->Items)) {
                $this->Items[$Group] = array();
            }

            $this->Items[$Group][] = array('Text' => $Text, 'Url' => $Url, 'Permission' => $Permission, 'Attributes' => $Attributes, 'AnchorAttributes' => $AnchorAttributes);
        }

        /**
         *
         *
         * @param $Group
         * @param $Text
         * @param bool $Permission
         * @param string $Attributes
         */
        public function addItem($Group, $Text, $Permission = false, $Attributes = '') {
            if (!array_key_exists($Group, $this->Items)) {
                $this->Items[$Group] = array();
            }

            $this->Items[$Group][] = array('Text' => $Text, 'Url' => false, 'Permission' => $Permission, 'Attributes' => $Attributes);
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
            $this->Items = array();
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
            if (array_key_exists($Group, $this->Items) && is_array($this->Items[$Group])) {
                foreach ($this->Items[$Group] as $Index => $GroupArray) {
                    if ($this->Items[$Group][$Index]['Text'] == $Text) {
                        unset($this->Items[$Group][$Index]);
                        array_merge($this->Items[$Group]);
                        break;
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
            if ($HighlightRoute == '') {
                $HighlightRoute = $this->_HighlightRoute;
            }

            if ($HighlightRoute == '') {
                $HighlightRoute = Gdn_Url::Request();
            }

            $this->fireEvent('BeforeToString');

            $Username = '';
            $UserID = '';
            $Session_TransientKey = '';
            $Permissions = array();
            $Session = Gdn::session();
            $HasPermissions = false;
            $Admin = false;
            if ($Session->isValid() === true) {
                $UserID = $Session->User->UserID;
                $Username = $Session->User->Name;
                $Session_TransientKey = $Session->TransientKey();
                $Permissions = $Session->GetPermissions();
                $HasPermissions = count($Permissions) > 0;
                $Admin = $Session->User->Admin > 0 ? true : false;
            }

            $Menu = '';
            if (count($this->Items) > 0) {
                // Apply the menu group sort if present...
                if (is_array($this->Sort)) {
                    $Items = array();
                    $Count = count($this->Sort);
                    for ($i = 0; $i < $Count; ++$i) {
                        $Group = $this->Sort[$i];
                        if (array_key_exists($Group, $this->Items)) {
                            $Items[$Group] = $this->Items[$Group];
                            unset($this->Items[$Group]);
                        }
                    }
                    foreach ($this->Items as $Group => $Links) {
                        $Items[$Group] = $Links;
                    }
                } else {
                    $Items = $this->Items;
                }
                foreach ($Items as $GroupName => $Links) {
                    $ItemCount = 0;
                    $LinkCount = 0;
                    $OpenGroup = false;
                    $Group = '';
                    foreach ($Links as $Key => $Link) {
                        $CurrentLink = false;
                        $ShowLink = false;
                        $RequiredPermissions = array_key_exists('Permission', $Link) ? $Link['Permission'] : false;
                        if ($RequiredPermissions !== false && !is_array($RequiredPermissions)) {
                            $RequiredPermissions = explode(',', $RequiredPermissions);
                        }

                        // Show if there are no permissions or the user has ANY of the specified permissions or the user is admin
                        $ShowLink = $Admin || $RequiredPermissions === false || ArrayInArray($RequiredPermissions, $Permissions, false) === true;

                        if ($ShowLink === true) {
                            if ($ItemCount == 1) {
                                $Group .= '<ul>';
                                $OpenGroup = true;
                            } elseif ($ItemCount > 1) {
                                $Group .= "</li>\r\n";
                            }

                            $Url = arrayValue('Url', $Link);
                            if (substr($Link['Text'], 0, 1) === '\\') {
                                $Text = substr($Link['Text'], 1);
                            } else {
                                $Text = str_replace('{Username}', $Username, $Link['Text']);
                            }
                            $Attributes = arrayValue('Attributes', $Link, array());
                            $AnchorAttributes = arrayValue('AnchorAttributes', $Link, array());
                            if ($Url !== false) {
                                $Url = url(str_replace(array('{Username}', '{UserID}', '{Session_TransientKey}'), array(urlencode($Username), $UserID, $Session_TransientKey), $Link['Url']));
                                $CurrentLink = $Url == url($HighlightRoute);

                                $CssClass = arrayValue('class', $Attributes, '');
                                if ($CurrentLink) {
                                    $Attributes['class'] = $CssClass.' Highlight';
                                }

                                $Group .= '<li'.Attribute($Attributes).'><a'.Attribute($AnchorAttributes).' href="'.$Url.'">'.$Text.'</a>';
                                ++$LinkCount;
                            } else {
                                $Group .= '<li'.Attribute($Attributes).'>'.$Text;
                            }
                            ++$ItemCount;
                        }
                    }
                    if ($OpenGroup === true) {
                        $Group .= "</li>\r\n</ul>\r\n";
                    }

                    if ($Group != '' && $LinkCount > 0) {
                        $Menu .= $Group."</li>\r\n";
                    }
                }
                if ($Menu != '') {
                    $Menu = '<ul id="'.$this->HtmlId.'"'.($this->CssClass != '' ? ' class="'.$this->CssClass.'"' : '').'>'.$Menu.'</ul>';
                }
            }
            return $Menu;
        }
    }
}
