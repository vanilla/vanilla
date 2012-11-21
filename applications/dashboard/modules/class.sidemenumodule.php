<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

if (!class_exists('SideMenuModule', FALSE)) {
   /**
    * Manages the items in the page menu and eventually returns the menu as a
    * string with ToString();
    */
   class SideMenuModule extends Gdn_Module {
      
      /**
       * Should the group titles be autolinked to the first anchor in the group? Default TRUE;
       */
      public $AutoLinkGroups;
      
      public $EventName = FALSE;
      
      /**
       * An array of menu items.
       */
      public $Items;

      protected $_Items;
      
      /**
       * The html id attribute to be applied to the root element of the menu.
       * Default is "Menu".
       */
      public $HtmlId;
      
      /**
       * The class attribute to be applied to the root element of the
       * breadcrumb. Default is none.
       */
      public $CssClass;
      
      /**
       * An array of menu group names arranged in the order that the menu
       * should be rendered.
       */
      public $Sort;
      
      /**
       * A route that, if found in the menu links, should cause that link to
       * have the Highlight class applied. This property is assigned with
       * $this->Highlight();
       */
      private $_HighlightRoute;
   
      public function __construct($Sender = '') {
         parent::__construct($Sender);
         
         $this->_ApplicationFolder = 'dashboard';
         $this->HtmlId = 'SideMenu';
         $this->AutoLinkGroups = TRUE;
         $this->ClearGroups();
      }
      
      public function AddLink($Group, $Text, $Url, $Permission = FALSE, $Attributes = array()) {
         if (!array_key_exists($Group, $this->Items)) {
            $this->AddItem($Group, T($Group));
         }
         if ($Text === FALSE) {
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
      
      public function AddItem($Group, $Text, $Permission = FALSE, $Attributes = array()) {
         if (!array_key_exists($Group, $this->Items))
            $Item = array('Group' => $Group, 'Links' => array(), 'Attributes' => array(), '_Sort' => count($this->Items));
         else {
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
      
      public function AssetTarget() {
         return 'Menu';
      }

      public function CheckPermissions() {
         $Session = Gdn::Session();

         foreach ($this->Items as $Group => $Item) {
            if (GetValue('Permission', $Item) && !$Session->CheckPermission($Item['Permission'])) {
               unset($this->Items[$Group]);
               continue;
            }

            foreach ($Item['Links'] as $Key => $Link) {
               if (GetValue('Permission', $Link) && !$Session->CheckPermission($Link['Permission']))
                  unset($this->Items[$Group]['Links'][$Key]);
            }
            // Remove the item if there are no more links.
            if (!GetValue('Url', $Item) && !count($this->Items[$Group]['Links']))
               unset($this->Items[$Group]);
         }
      }
      
      public function ClearGroups() {
         $this->Items = array();
      }

      protected function _Compare($A, $B = NULL) {
         static $Groups;
         if ($B === NULL) {
            $Groups = $A;
            return;
         }

         $SortA = $this->_CompareSort($A, $Groups);
         $SortB = $this->_CompareSort($B, $Groups);

         if ($SortA > $SortB)
            return 1;
         elseif ($SortA < $SortB)
            return -1;
         elseif ($A['_Sort'] > $B['_Sort']) // fall back to order added
            return 1;
         elseif ($A['_Sort'] < $B['_Sort'])
            return -1;
         else
            return 0;
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
         if (isset($A['Sort']))
            return $A['Sort'];
         if (isset($A['After']) && isset($All[$A['After']])) {
            $After = $All[$A['After']];
            if (isset($After['Sort']))
               return $After['Sort'] + 0.1;
            return $After['_Sort'] + 0.1;
         }
         
         return $A['_Sort'];
      }
      
      public function HighlightRoute($Route) {
         $this->_HighlightRoute = $Route;
      }
      
      public function RemoveLink($Group, $Text) {
         if (array_key_exists($Group, $this->Items) && isset($this->Items[$Group]['Links'])) {
            $Links =& $this->Items[$Group]['Links'];

            if (isset($Links[$Text])) {
               unset($this->Items[$Group]['Links'][$Text]);
               return;
            }


            foreach ($Links as $Index => $Link) {
               if (GetValue('Text', $Link) == $Text) {
                  unset($this->Items[$Group]['Links'][$Index]);
                  return;
               }
            }
         }
      }
      
      /**
       * Removes all links from a specific group.
       */
      public function RemoveLinks($Group) {
			$this->Items[$Group] = array();
      }
      
      /**
       * Removes an entire group of links, and the group itself, from the menu.
       */
      public function RemoveGroup($Group) {
         if (array_key_exists($Group, $this->Items))
            unset($this->Items[$Group]);
      }

      public function ToString($HighlightRoute = '') {
         Gdn::Controller()->EventArguments['SideMenu'] = $this;
         if ($this->EventName)
            Gdn::Controller()->FireEvent($this->EventName);
         
         
         if ($HighlightRoute == '')
            $HighlightRoute = $this->_HighlightRoute;
         if ($HighlightRoute == '')
            $HighlightRoute = Gdn_Url::Request();
         $HighlightUrl = Url($HighlightRoute);

         // Apply a sort to the items if given.
         if (is_array($this->Sort)) {
            $Sort = array_flip($this->Sort);
            foreach ($this->Items as $Group => &$Item) {
               if (isset($Sort[$Group]))
                  $Item['Sort'] = $Sort[$Group];
               else
                  $Item['_Sort'] += count($Sort);

               foreach ($Item['Links'] as $Url => &$Link) {
                  if (isset($Sort[$Url]))
                     $Link['Sort'] = $Sort[$Url];
                  elseif (isset($Sort[$Link['Text']]))
                     $Link['Sort'] = $Sort[$Link['Text']];
                  else
                     $Link['_Sort'] += count($Sort);
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
            if (GetValue('Url', $Item) && Url($Item['Url']) == $HighlightUrl)
               $Item['Attributes']['class'] = ConcatSep(' ', GetValue('class', $Item['Attributes']), 'Active');

            // Hightlight the correct item in the group.
            foreach ($Item['Links'] as &$Link) {
               if (GetValue('Url', $Link) && Url($Link['Url']) == $HighlightUrl) {
                  $Link['Attributes']['class'] = ConcatSep(' ', GetValue('class', $Link['Attributes']), 'Active');
                  $Item['Attributes']['class'] = ConcatSep(' ', GetValue('class', $Item['Attributes']), 'Active');
               }
            }
         }

         return parent::ToString();
      }
   }
}
