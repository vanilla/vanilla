<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

if (!class_exists('MenuModule', FALSE)) {
   /**
    * Manages the items in the page menu and eventually returns the menu as a
    * string with ToString();
    */
   class MenuModule extends Gdn_Module {
      
      /**
       * An array of menu items.
       */
      public $Items;
      
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
         $this->HtmlId = 'Menu';
         $this->ClearGroups();
         parent::__construct($Sender);
      }
      
      public function AddLink($Group, $Text, $Url, $Permission = FALSE, $Attributes = '', $AnchorAttributes = '') {
         if (!array_key_exists($Group, $this->Items))
            $this->Items[$Group] = array();

         $this->Items[$Group][] = array('Text' => $Text, 'Url' => $Url, 'Permission' => $Permission, 'Attributes' => $Attributes, 'AnchorAttributes' => $AnchorAttributes);
      }
      
      public function AddItem($Group, $Text, $Permission = FALSE, $Attributes = '') {
         if (!array_key_exists($Group, $this->Items))
            $this->Items[$Group] = array();

         $this->Items[$Group][] = array('Text' => $Text, 'Url' => FALSE, 'Permission' => $Permission, 'Attributes' => $Attributes);
      }      
      
      public function AssetTarget() {
         return 'Menu';
      }
      
      public function ClearGroups() {
         $this->Items = array();
      }
      
      public function HighlightRoute($Route) {
         $this->_HighlightRoute = $Route;
      }
      
      public function RemoveLink($Group, $Text) {
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
         if ($HighlightRoute == '')
            $HighlightRoute = $this->_HighlightRoute;
            
         if ($HighlightRoute == '')
            $HighlightRoute = Gdn_Url::Request();
            
         $this->FireEvent('BeforeToString');
         
         $Username = '';
         $UserID = '';
         $Session_TransientKey = '';
         $Permissions = array();
         $Session = Gdn::Session();
         $HasPermissions = FALSE;
         $Admin = FALSE;
         if ($Session->IsValid() === TRUE) {
            $UserID = $Session->User->UserID;
            $Username = $Session->User->Name;
            $Session_TransientKey = $Session->TransientKey();
            $Permissions = $Session->GetPermissions();
            $HasPermissions = count($Permissions) > 0;
            $Admin = $Session->User->Admin > 0 ? TRUE : FALSE;
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
               $OpenGroup = FALSE;
               $Group = '';
               foreach ($Links as $Key => $Link) {
                  $CurrentLink = FALSE;
                  $ShowLink = FALSE;
                  $RequiredPermissions = array_key_exists('Permission', $Link) ? $Link['Permission'] : FALSE;
                  if ($RequiredPermissions !== FALSE && !is_array($RequiredPermissions))
                     $RequiredPermissions = explode(',', $RequiredPermissions);
                     
                  // Show if there are no permissions or the user has the required permissions or the user is admin
                  $ShowLink = $Admin || $RequiredPermissions === FALSE || ArrayInArray($RequiredPermissions, $Permissions, FALSE) === TRUE;
                  
                  if ($ShowLink === TRUE) {
                     if ($ItemCount == 1) {
                        $Group .= '<ul>';
                        $OpenGroup = TRUE;
                     } else if ($ItemCount > 1) {
                        $Group .= "</li>\r\n";
                     }
                     
                     $Url = ArrayValue('Url', $Link);
                     if(substr($Link['Text'], 0, 1) === '\\') {
                        $Text = substr($Link['Text'], 1);
                     } else {
                        $Text = str_replace('{Username}', $Username, $Link['Text']);
                     }
                     $Attributes = ArrayValue('Attributes', $Link, array());
                     $AnchorAttributes = ArrayValue('AnchorAttributes', $Link, array());
                     if ($Url !== FALSE) {
                        $Url = Url(str_replace(array('{Username}', '{UserID}', '{Session_TransientKey}'), array(urlencode($Username), $UserID, $Session_TransientKey), $Link['Url']));
                        $CurrentLink = $Url == Url($HighlightRoute);
                        
                        $CssClass = ArrayValue('class', $Attributes, '');
                        if ($CurrentLink)
                           $Attributes['class'] = $CssClass . ' Highlight';
								
                        $Group .= '<li'.Attribute($Attributes).'><a'.Attribute($AnchorAttributes).' href="'.$Url.'">'.$Text.'</a>';
                        ++$LinkCount;
                     } else {
                        $Group .= '<li'.Attribute($Attributes).'>'.$Text;
                     }
                     ++$ItemCount;
                  }
               }
               if ($OpenGroup === TRUE)
                  $Group .= "</li>\r\n</ul>\r\n";

               if ($Group != '' && $LinkCount > 0)
                  $Menu .= $Group . "</li>\r\n";
            }
            if ($Menu != '')
               $Menu = '<ul id="'.$this->HtmlId.'"'.($this->CssClass != '' ? ' class="'.$this->CssClass.'"' : '').'>'.$Menu.'</ul>';
         }
         return $Menu;
      }
   }
}
