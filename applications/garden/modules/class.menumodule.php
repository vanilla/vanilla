<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/// <namespace>
/// Lussumo.Garden.UI
/// </namespace>

if (!class_exists('Gdn_MenuModule', FALSE)) {
   /// <summary>
   /// Manages the items in the page menu and eventually returns the menu as a
   /// string with ToString();
   /// </summary>
   class Gdn_MenuModule extends Module {
      
      /// <prop type="array">
      /// An array of menu items.
      /// </prop>
      public $Items;
      
      /// <prop type="string">
      /// The html id attribute to be applied to the root element of the menu.
      /// Default is "Menu".
      /// </prop>
      public $HtmlId;
      
      /// <prop type="string">
      /// The class attribute to be applied to the root element of the
      /// breadcrumb. Default is none.
      /// </prop>
      public $CssClass;
      
      /// <prop type="array">
      /// An array of menu group names arranged in the order that the menu
      /// should be rendered.
      /// </prop>
      public $Sort;
      
      /// <prop type="string">
      /// A route that, if found in the menu links, should cause that link to
      /// have the Highlight class applied. This property is assigned with
      /// $this->Highlight();
      /// </prop>
      private $_HighlightRoute;
   
      public function __construct(&$Sender = '') {
         $this->HtmlId = 'Menu';
         $this->ClearGroups();
         parent::__construct($Sender);
      }
      
      public function AddLink($Group, $Code, $Url, $Permission = FALSE, $Attributes = '') {
         if (!array_key_exists($Group, $this->Items))
            $this->Items[$Group] = array();

         $this->Items[$Group][] = array('Code' => $Code, 'Url' => $Url, 'Permission' => $Permission, 'Attributes' => $Attributes);
      }
      
      public function AddItem($Group, $Code, $Permission = FALSE, $Attributes = '') {
         if (!array_key_exists($Group, $this->Items))
            $this->Items[$Group] = array();

         $this->Items[$Group][] = array('Code' => $Code, 'Url' => FALSE, 'Permission' => $Permission, 'Attributes' => $Attributes);
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

      /// <summary>
      /// Removes all links from a specific group.
      /// </summary>
      public function RemoveLinks($Group) {
         $this->Links[$Group] = array();
      }
      
      /// <summary>
      /// Removes an entire group of links, and the group itself, from the menu.
      /// </summary>
      public function RemoveGroup($Group) {
         if (array_key_exists($Group, $this->Groups))
            unset($this->Groups[$Group]);
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
                     $Text = str_replace('{Username}', $Username, Gdn::Translate($Link['Code']));
                     $Attributes = ArrayValue('Attributes', $Link, array());
                     if ($Url !== FALSE) {
                        $Url = str_replace(array('{Username}', '{UserID}', '{Session_TransientKey}'), array(urlencode($Username), $UserID, $Session_TransientKey), $Link['Url']);
                        if (substr($Url, 0, 5) != 'http:') {
                           $Url = Url($Url);
                           $CurrentLink = $Url == Url($HighlightRoute);
                        }
                        
                        $CssClass = ArrayValue('class', $Attributes, '');
                        if ($CurrentLink)
                           $Attributes['class'] = $CssClass . ' Highlight';
                           
                        $Group .= '<li'.Attribute($Attributes).'><a href="'.$Url.'">'.$Text.'</a>';
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