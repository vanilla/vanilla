<?php if (!defined('APPLICATION')) exit();

/**
 * SignedIn Module
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class SignedInModule extends Gdn_Module {
   
   public function AssetTarget() {
      $this->_ApplicationFolder = 'dashboard';
      return 'Panel';
   }
}