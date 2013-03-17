<?php if (!defined('APPLICATION')) exit();

/**
 * Message Module
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class MessageModule extends Gdn_Module {

   protected $_Message;

   public function __construct($Sender = '', $Message = FALSE) {
      parent::__construct($Sender);
      
      $this->_ApplicationFolder = 'dashboard';
      $this->_Message = $Message;
   }
   
   public function AssetTarget() {
      return $this->_Message == FALSE ? 'Content' : GetValue('AssetTarget', $this->_Message);
   }
   
}