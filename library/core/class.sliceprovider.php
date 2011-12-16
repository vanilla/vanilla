<?php if (!defined('APPLICATION')) exit();

/**
 * Slice manager: plugins and controllers
 * 
 * Allows plugns and controllers to implement small asynchronously refreshable 
 * portions of the page - slices.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_SliceProvider {

   protected $SliceHandler;
   protected $SliceConfig;

   public function EnableSlicing($Sender) {
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