<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Allows the user to show all unfollowed categories so they can re-follow them.
 */
class CategoryFollowToggleModule extends Gdn_Module {
   
   /**
    * Set the preference in the user's session.
    */
   public function SetToggle() {
      $Session = Gdn::Session();
      if (!$Session->IsValid())
         return;
      
      $ShowAllCategories = GetIncomingValue('ShowAllCategories', '');
      if ($ShowAllCategories != '') {
         $ShowAllCategories = $ShowAllCategories == 'true' ? TRUE : FALSE;
         $ShowAllCategoriesPref = $Session->GetPreference('ShowAllCategories');
         if ($ShowAllCategories != $ShowAllCategoriesPref)
            $Session->SetPreference('ShowAllCategories', $ShowAllCategories);
            
         Redirect('/'.ltrim(Gdn::Request()->Path(), '/'));
      }
   }
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (Gdn::Session()->IsValid())
         return parent::ToString();

      return '';
   }
}