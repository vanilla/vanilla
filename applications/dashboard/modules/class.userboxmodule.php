<?php if (!defined('APPLICATION')) exit();

/**
 * UserBox Module
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class UserBoxModule extends Gdn_Module {
   
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'dashboard';
   }
   
   public function AssetTarget() {
      return 'Panel';
   }
}