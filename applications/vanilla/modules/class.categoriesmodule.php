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

   public $startDepth = 1; //inclusive
   public $endDepth; //inclusive

   public function __construct($Sender = '') {
      parent::__construct($Sender);
      $this->_ApplicationFolder = 'vanilla';

      $this->Visible = C('Vanilla.Categories.Use') && !C('Vanilla.Categories.HideModule');
   }

   public function AssetTarget() {
      return 'Panel';
   }

   /**
    * Get the data for this module.
    */
   protected function GetData() {
      // Allow plugins to set different data.
      $this->FireEvent('GetData');
      if ($this->Data) {
         return;
      }

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

   public function filterDepth(&$Categories, $startDepth, $endDepth) {
      if ($startDepth != 1 || $endDepth) {
         foreach ($Categories as $i => $Category) {
            if (val('Depth', $Category) < $startDepth || ($endDepth && val('Depth', $Category) > $endDepth)) {
               unset($Categories[$i]);
            }
         }
      }
   }

   public function ToString() {
      if (!$this->Data) {
         $this->GetData();
      }

      $this->filterDepth($this->Data->Result(), $this->startDepth, $this->endDepth);

      return parent::ToString();
   }
}
