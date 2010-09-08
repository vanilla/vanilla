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
 * Allows plugns and controllers to implement slices -- small asynchronously refreshable portions of the page
 *
 * @author Tim Gunter
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_SliceProvider {

   protected $SliceHandler;
   protected $SliceConfig;

   public function EnableSlicing(&$Sender) {
      $this->SliceHandler = $Sender;
      $this->SliceConfig = array(
         'css'       => array(),
         'js'        => array()
      );
      $Sender->AddJsFile('/js/library/jquery.class.js');
      $Sender->AddJsFile('/js/slice.js');
      $Sender->AddCssFile('/applications/dashboard/design/slice.css');
   }

   public function Slice($SliceName, $Arguments = array()) {
      $CurrentPath = Gdn::Request()->Path();
      $ExplodedPath = explode('/',$CurrentPath);
      switch ($this instanceof Gdn_IPlugin) {
         case TRUE:
            $ReplacementIndex = 2;
         break;
         
         case FALSE:
            $ReplacementIndex = 1;
         break;
      }
      
      if ($ExplodedPath[0] == strtolower(Gdn::Dispatcher()->Application()) && $ExplodedPath[1] == strtolower(Gdn::Dispatcher()->Controller()))
         $ReplacementIndex++;

      $ExplodedPath[$ReplacementIndex] = $SliceName;
      $SlicePath = implode('/',$ExplodedPath);
      return Gdn::Slice($SlicePath);
   }
   
   public function AddSliceAsset($Asset) {
      $Extension = strtolower(array_pop($Trash = explode('.',basename($Asset))));
      switch ($Extension) {
         case 'css':
            if (!in_array($Asset, $this->SliceConfig['css'])) 
               $this->SliceConfig['css'][] = $Asset;
            break;
            
         case 'js':
            if (!in_array($Asset, $this->SliceConfig['js'])) 
               $this->SliceConfig['js'][] = $Asset;
            break;
      }
   }
   
   public function RenderSliceConfig() {
      return json_encode($this->SliceConfig);
   }

}