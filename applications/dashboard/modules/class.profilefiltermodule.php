<?php if (!defined('APPLICATION')) exit();

/**
 * Renders the profile filter menu
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class ProfileFilterModule extends Gdn_Module {
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      return parent::ToString();
   }
}