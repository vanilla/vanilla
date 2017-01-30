<?php
/**
 * Vanilla model
 *
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Introduces common methods that child classes can use.
 */
abstract class VanillaModel extends Gdn_Model {

    /**
     * Class constructor. Defines the related database table name.
     *
     * @since 2.0.0
     * @access public
     * @deprecated
     *
     * @param string $Name Database table name.
     */
    public function __construct($Name = '') {
        deprecated('Extending VanillaModel', 'Gdn_Model');
        parent::__construct($Name);
    }

    /**
     * Checks to see if the user is spamming. Returns TRUE if the user is spamming.
     *
     * Users cannot post more than $SpamCount comments within $SpamTime
     * seconds or their account will be locked for $SpamLock seconds.
     *
     * @since 2.0.0
     * @access public
     * @deprecated
     *
     * @param string $type Valid values are 'Comment' or 'Discussion'.
     * @return bool Whether spam check is positive (true = spammer).
     */
    public function checkForSpam($type) {
        deprecated(__CLASS__.' '.__METHOD__, 'FloodControl::isCurrentUserSpamming()');

        // Flood control check (spamming)
        $floodControl = FloodControl::getInstance();

        // Respect model attribute
        if (!val('SpamCheck', $this, true)) {
            $floodControl->setFloodControlState($type, false);
        }

        $isUserSpamming = $floodControl->isCurrentUserSpamming($type);
        if ($isUserSpamming) {
            $this->Validation->addValidationResult(
                'Body',
                '@'.$floodControl->getWarningMessage($type)
            );
        }

        return $Spam;
    }
}
