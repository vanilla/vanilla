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
 * A module that contains other modules.
 */
class Gdn_ModuleCollection extends Gdn_Module {
   /// PROPERTIES ///
   public $Items = array();
   
   /// METHODS ///
   public function Render() {
      $RenderedCount = 0;
      foreach($this->Items as $Item) {
         $this->EventArguments['AssetName'] = $this->AssetName;

         if(is_string($Item)) {
            if (!empty($Item)) {
               if ($RenderedCount > 0)
                  $this->FireEvent('BetweenRenderAsset');

               echo $Item;
               $RenderedCount++;
            }
         } elseif($Item instanceof Gdn_IModule) {
            $LengthBefore = ob_get_length();
            $Item->Render();
            $LengthAfter = ob_get_length();

            if ($LengthBefore !== FALSE && $LengthAfter > $LengthBefore) {
               if ($RenderedCount > 0)
                  $this->FireEvent('BetweenRenderAsset');
               $RenderedCount++;
            }
         } else {
            throw new Exception();
         }
      }
   }
   
   public function ToString() {
      ob_start();
      $this->Render();
      return ob_get_clean();
   }
}