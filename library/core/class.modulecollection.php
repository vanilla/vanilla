<?php if (!defined('APPLICATION')) exit();

/**
 * Module collection
 *
 * @author Todd Burry <todd@vanillaforums.com> 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_ModuleCollection extends Gdn_Module {
   /// PROPERTIES ///
   public $Items = array();
   
   /// METHODS ///
   public function Render() {
      $RenderedCount = 0;
      foreach ($this->Items as $Item) {
         $this->EventArguments['AssetName'] = $this->AssetName;

         if (is_string($Item)) {
            if (!empty($Item)) {
               if ($RenderedCount > 0)
                  $this->FireEvent('BetweenRenderAsset');

               echo $Item;
               $RenderedCount++;
            }
         } elseif ($Item instanceof Gdn_IModule) {
            if (!GetValue('Visible', $Item, TRUE))
               continue;
            
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
      unset($this->EventArguments['AssetName']);
   }
   
   public function ToString() {
      ob_start();
      $this->Render();
      return ob_get_clean();
   }
}