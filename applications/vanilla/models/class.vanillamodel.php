<?php
/**
 * Vanilla model
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
     *
     * @param string $Name Database table name.
     */
    public function __construct($Name = '') {
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
     *
     * @param string $Type Valid values are 'Comment' or 'Discussion'.
     * @return bool Whether spam check is positive (TRUE = spammer).
     */
    public function checkForSpam($Type) {
        $Session = Gdn::session();

        // If spam checking is disabled or user is an admin, skip
        $SpamCheckEnabled = val('SpamCheck', $this, true);
        if ($SpamCheckEnabled === false || $Session->User->Admin || $Session->checkPermission('Garden.Moderation.Manage')) {
            return false;
        }

        $Spam = false;

        // Validate $Type
        if (!in_array($Type, array('Comment', 'Discussion'))) {
            trigger_error(ErrorMessage(sprintf('Spam check type unknown: %s', $Type), 'VanillaModel', 'CheckForSpam'), E_USER_ERROR);
        }

        $CountSpamCheck = $Session->getAttribute('Count'.$Type.'SpamCheck', 0);
        $DateSpamCheck = $Session->getAttribute('Date'.$Type.'SpamCheck', 0);
        $SecondsSinceSpamCheck = time() - Gdn_Format::toTimestamp($DateSpamCheck);

        // Get spam config settings
        $SpamCount = Gdn::config('Vanilla.'.$Type.'.SpamCount');
        if (!is_numeric($SpamCount) || $SpamCount < 1) {
            $SpamCount = 1; // 1 spam minimum
        }
        $SpamTime = Gdn::config('Vanilla.'.$Type.'.SpamTime');
        if (!is_numeric($SpamTime) || $SpamTime < 30) {
            $SpamTime = 30; // 30 second minimum spam span
        }
        $SpamLock = Gdn::config('Vanilla.'.$Type.'.SpamLock');
        if (!is_numeric($SpamLock) || $SpamLock < 60) {
            $SpamLock = 60; // 60 second minimum lockout
        }
        // Apply a spam lock if necessary
        $Attributes = array();
        if ($SecondsSinceSpamCheck < $SpamLock && $CountSpamCheck >= $SpamCount && $DateSpamCheck !== false) {
            // TODO: REMOVE DEBUGGING INFO AFTER THIS IS WORKING PROPERLY
            /*
            echo '<div>SecondsSinceSpamCheck: '.$SecondsSinceSpamCheck.'</div>';
            echo '<div>SpamLock: '.$SpamLock.'</div>';
            echo '<div>CountSpamCheck: '.$CountSpamCheck.'</div>';
            echo '<div>SpamCount: '.$SpamCount.'</div>';
            echo '<div>DateSpamCheck: '.$DateSpamCheck.'</div>';
            echo '<div>SpamTime: '.$SpamTime.'</div>';
            */
            $Spam = true;
            $this->Validation->addValidationResult(
                'Body',
                '@'.sprintf(
                    t('You have posted %1$s times within %2$s seconds. A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
                    $SpamCount,
                    $SpamTime,
                    $SpamLock
                )
            );

            // Update the 'waiting period' every time they try to post again
            $Attributes['Date'.$Type.'SpamCheck'] = Gdn_Format::toDateTime();
        } else {
            if ($SecondsSinceSpamCheck > $SpamTime) {
                $Attributes['Count'.$Type.'SpamCheck'] = 1;
                $Attributes['Date'.$Type.'SpamCheck'] = Gdn_Format::toDateTime();
            } else {
                $Attributes['Count'.$Type.'SpamCheck'] = $CountSpamCheck + 1;
            }
        }
        // Update the user profile after every comment
        $UserModel = Gdn::userModel();
        if ($Session->UserID) {
            $UserModel->saveAttribute($Session->UserID, $Attributes);
        }

        return $Spam;
    }
}
