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
   
      public function __construct(&$Sender = '') {
         $this->HtmlId = 'SideMenu';
         $this->AutoLinkGroups = TRUE;
         $this->ClearGroups();
         parent::__construct($Sender);
      }
      
      public function AddLink($Group, $Text, $Url, $Permission = FALSE, $Attributes = '') {
         if (!array_key_exists($Group, $this->Items))
            $this->Items[$Group] = array();

         $this->Items[$Group][] = array('Text' => $Text, 'Url' => $Url, 'Permission' => $Permission, 'Attributes' => $Attributes);
      }
      
      public function AddItem($Group, $Text, $Permission = FALSE, $Attributes = '') {
         if (!array_key_exists($Group, $this->Items)) {
            $this->Items[$Group] = array();
            $this->Items[$Group][] = array('Text' => $Text, 'Url' => FALSE, 'Permission' => $Permission, 'Attributes' => $Attributes);
         }
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
            $Admin = $Session->User->Admin == '1' ? TRUE : FALSE;
         }
         
         $Menu = '';
         if (count($this->Items) > 0) {
            // Apply the menu sort if present...
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
                  $LinkNames = ConsolidateArrayValuesByKey($Links, 'Text');
                  $SortedLinks = array();
                  for ($j = 0; $j < $Count; ++$j) {
                     $SortName = $this->Sort[$j];
                     $Key = array_search($SortName, $LinkNames);
                     if ($Key !== FALSE) {
                        $SortedLinks[] = $Links[$Key];
                        unset($Links[$Key]);
                        $LinkNames[$Key] = '-=EMPTY=-';
                     }
                  }
                  $SortedLinks = array_merge($SortedLinks, $Links);
                  $Items[$Group] = $SortedLinks;
               }
            } else {
               $Items = $this->Items;
            }
            
            // Build the menu
            foreach ($Items as $GroupName => $Links) {
               $ItemCount = 0;
               $LinkCount = 0;
               $OpenGroup = FALSE;
               $GroupIsActive = FALSE;
               $GroupAnchor = '';
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
                        $Group .= '<ul class="PanelInfo">';
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
                     if ($Url !== FALSE) {
                        $Url = str_replace(array('{Username}', '{UserID}', '{Session_TransientKey}'), array(urlencode($Username), $UserID, $Session_TransientKey), $Link['Url']);
                        if (substr($Url, 0, 5) != 'http:') {
                           if ($GroupAnchor == '' && $this->AutoLinkGroups)
                              $GroupAnchor = $Url;
                              
                           $Url = Url($Url);
                           $CurrentLink = $Url == Url($HighlightRoute);
                           if ($CurrentLink && !$GroupIsActive) 
                              $GroupIsActive = TRUE;
                        }
                        
                        $CssClass = ArrayValue('class', $Attributes, '');
                        if ($CurrentLink)
                           $Attributes['class'] = $CssClass . ' Active';
                           
                        $Group .= '<li'.Attribute($Attributes).'><a href="'.$Url.'">'.$Text.'</a>';
                        ++$LinkCount;
                     }  else {
                        $GroupAttributes = $Attributes;
                        $GroupName = $Text;
                     }
                     ++$ItemCount;
                  }
               }
               if ($OpenGroup === TRUE) {
                  $Group .= "</li>\r\n</ul>\r\n";
                  $GroupAttributes['class'] = 'Box Group '.GetValue('class', $GroupAttributes, '');
                  if ($GroupIsActive)
                     $GroupAttributes['class'] .= ' Active';
                     
                  if ($GroupName != '') {
                     if ($LinkCount == 1 && $GroupName == $Text)
                        $Group = '';
                        
                     $GroupUrl = Url($GroupAnchor);
                     $Group = Wrap(Wrap(($GroupAnchor == '' ? $GroupName : "<a href=\"$GroupUrl\">$GroupName</a>" /*Anchor($GroupName, $GroupAnchor)*/), 'h4').$Group, 'div', $GroupAttributes);
                  }
               }


               if ($Group != '' && $LinkCount > 0) {
                  $Menu .= $Group . "\r\n";
               }

            }
            if ($Menu != '')
               $Menu = '<div'.($this->HtmlId == '' ? '' : ' id="'.$this->HtmlId.'"').' class="'.($this->CssClass != '' ? $this->CssClass : '').'">'.$Menu.'</div>';
         }
         return $Menu;
      }
   }
}
