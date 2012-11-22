<?php if (!defined('APPLICATION')) exit();

/**
 * Renders a user's photo (if they've uploaded one).
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class UserPhotoModule extends Gdn_Module {
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'dashboard';
   }
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
		return parent::ToString();
   }
}