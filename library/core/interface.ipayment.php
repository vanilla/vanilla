<?php
/**
 * Payment interface
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.18
 */

/**
 * An interface that all payment plugins must follow.
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
