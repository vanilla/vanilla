<?php if (!defined('APPLICATION')) exit();

/**
 * Payment interface
 * 
 * An interface that all payment plugins must follow.
 * 
 * @author Matt Russell <lincoln@vanillaforums.com
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0.18
 */

interface Gdn_IPayment extends Gdn_IPlugin {
   /**
    * Initiate a payment with the remote processor.
    *
    * @param array $Data Information required for the transaction.
    *    Keys: Amount (int), UserID (int), Token (string), Description (string), Card (array).
    * @return bool Whether payment was successful.
    */
   public function SendPayment($Data);
}