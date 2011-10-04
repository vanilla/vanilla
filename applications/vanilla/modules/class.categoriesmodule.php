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
 * Renders the discussion categories.
 */
class CategoriesModule extends Gdn_Module {
   
   public function __construct($Sender = '') {
      // Load categories
      $this->Data = FALSE;
      if (C('Vanilla.Categories.Use') == TRUE && !C('Vanilla.Categories.HideModule')) {
         $Categories = CategoryModel::Categories();
         $Categories2 = $Categories;
         
         // Filter out the categories we aren't watching.
         foreach ($Categories2 as $i => $Category) {
            if (!$Category['PermsDiscussionsView'] || !$Category['Following']) {
               unset($Categories[$i]);
            }
         }
         
         $Data = new Gdn_DataSet($Categories);
         $Data->DatasetType(DATASET_TYPE_ARRAY);
         $Data->DatasetType(DATASET_TYPE_OBJECT);
         $this->Data = $Data;
      }
      parent::__construct($Sender);
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if ($this->Data)
         return parent::ToString();

      return '';
   }
}