<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ToggleMenuModule extends Gdn_Module {
   
   private $_Labels = array();
   public function AddLabel($Name, $Code = '', $Url = '') {
      if ($Code == '')
         $Code = Gdn_Format::Url(ucwords(trim(Gdn_Format::PlainText($Name))));

      $this->_Labels[] = array('Name' => $Name, 'Code' => $Code, 'Url' => $Url);
   }
   
   private $_CurrentLabelCode = FALSE;
   public function CurrentLabelCode($Label = '') {
      if ($Label != '')
         $this->_CurrentLabelCode = $Label;
      
      // If the current code hasn't been assigned, use the first available label
      if (!$this->_CurrentLabelCode && count($this->_Labels) > 0)
         return $this->_Labels[0]['Code'];

      return $this->_CurrentLabelCode;
   }
   
   public function ToString() {
      $Return = '<ul class="FilterMenu ToggleMenu">';
      foreach ($this->_Labels as $Label) {
         $Url = GetValue('Url', $Label, '');
         if ($Url == '')
            $Url = '#';
         
         $Name = GetValue('Name', $Label, '');
         $Code = GetValue('Code', $Label, '');
         $Active = strcasecmp($Code, $this->CurrentLabelCode()) == 0;
         $CssClass = 'Handle-'.$Code;
         $AnchorClass = '';
         if ($Active) {
            $CssClass .= ' Active';
            $AnchorClass = 'TextColor';
         }

         $Return .= '<li class="'.$CssClass.'">';
            $Return .= Anchor($Name, $Url, $AnchorClass);
         $Return .= '</li>';
      }
      $Return .= '</ul>';   
      return $Return;
   }
}