<?php
/**
 * Vanilla model
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Introduces common methods that child classes can use.
 *
 * @deprecated
 */
abstract class VanillaModel extends Gdn_Model {

    use \Vanilla\FloodControlTrait;

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     *
     * @param string $name Database table name.
     */
    public function __construct($name = '') {
        parent::__construct($name);
    }

    /**
     * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
     *
     * Users cannot post more than $SpamCount comments within $SpamTime
     * seconds or their account will be locked for $SpamLock seconds.
     *
     * @deprecated
     *
     * @param string $type Valid values are 'Comment' or 'Discussion'.
     * @return bool Whether spam check is positive (TRUE = spammer).
     */
    public function checkForSpam($type) {
        deprecated(__CLASS__.' '.__METHOD__, 'FloodControlTrait::checkUserSpamming()');

        $session = Gdn::session();

        // Validate $Type
        if (!in_array($type, ['Comment', 'Discussion'])) {
            trigger_error(errorMessage(sprintf('Spam check type unknown: %s', $type), 'VanillaModel', 'CheckForSpam'), E_USER_ERROR);
        }

        $storageObject = FloodControlHelper::configure($this, 'Vanilla', $type);
        $isUserSpamming = $this->checkUserSpamming($session->User->UserID, $storageObject);

        return $isUserSpamming;
    }
}
