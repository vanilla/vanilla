<?php

final class FloodControl {

    const TYPE_COMMENT = 'Comment';
    const TYPE_DISCUSSION = 'Discussion';
    const TYPE_ACTIVITY = 'Activity';
    const TYPE_ACTIVITY_COMMENT = 'ActivityComment';

    /** @var FloodControl */
    private static $instance;

     /** @var Array */
    private $floodControlStates = [
        self::TYPE_COMMENT => true,
        self::TYPE_DISCUSSION => true,
        self::TYPE_ACTIVITY => true,
        self::TYPE_ACTIVITY_COMMENT => true,
    ];

    /**
     * Get the FloodControl instance.
     *
     * @return FloodControl
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new FloodControl();
        }
        return self::$instance;
    }

    /**
     * Enable or disable flood control check for a specific type.
     *
     * @param $type Type of record. Valid values are FloodControl::TYPE_*.
     * @param $active Whether flood control is active or not.
     *
     * @return FloodControl self
     */
    public function setFloodControlState($type, $active) {
        $this->floodControlStates[$type] = $active;
        return $this;
    }

    /**
     * Checks to see if the user is spamming.
     *
     * Users cannot post more than $spamCount TYPE within $spamTime
     * seconds or their account will be locked for $spamLock seconds.
     *
     * @param string $type Type of record. Valid values are FloodControl::TYPE_*.
     * @return bool Whether the current user is spamming or not.
     */
    public function isCurrentUserSpamming($type) {
        // Validate $type
        if (!isset($this->floodControlStates[$type])) {
            trigger_error(errorMessage(sprintf('FloodControl type unknown: %s', $type), __CLASS__, __METHOD__), E_USER_ERROR);
            return false;
        }

        $session = Gdn::session();

        // If spam checking is disabled or user is an admin, skip
        $floodControlEnabled = val($type, $this->floodControlStates);
        if ($floodControlEnabled === false || $session->User->Admin || $session->checkPermission('Garden.Moderation.Manage')) {
            return false;
        }

        $spam = false;

        $countSpamCheck = $session->getAttribute('Count'.$type.'SpamCheck', 0);
        $dateSpamCheck = $session->getAttribute('Date'.$type.'SpamCheck', 0);
        $secondsSinceSpamCheck = time() - Gdn_Format::toTimestamp($dateSpamCheck);

        // Get spam config settings
        $spamCount = Gdn::config('Vanilla.'.$type.'.SpamCount');
        if (!is_numeric($spamCount) || $spamCount < 1) {
            $spamCount = 1; // 1 spam minimum
        }
        $spamTime = Gdn::config('Vanilla.'.$type.'.SpamTime');
        if (!is_numeric($spamTime) || $spamTime < 30) {
            $spamTime = 30; // 30 second minimum spam span
        }
        $spamLock = Gdn::config('Vanilla.'.$type.'.SpamLock');
        if (!is_numeric($spamLock) || $spamLock < 60) {
            $spamLock = 60; // 60 second minimum lockout
        }

        // Apply a spam lock if necessary
        $attributes = [];
        if ($secondsSinceSpamCheck < $spamLock && $countSpamCheck >= $spamCount && $dateSpamCheck !== false) {
            $spam = true;

            // Update the 'waiting period' every time they try to post again
            $attributes['Date'.$type.'SpamCheck'] = Gdn_Format::toDateTime();
        } else {
            if ($secondsSinceSpamCheck > $spamTime) {
                $attributes['Count'.$type.'SpamCheck'] = 1;
                $attributes['Date'.$type.'SpamCheck'] = Gdn_Format::toDateTime();
            } else {
                $attributes['Count'.$type.'SpamCheck'] = $countSpamCheck + 1;
            }
        }

        // Update the user profile after every comment
        $userModel = Gdn::userModel();
        if ($session->UserID) {
            $userModel->saveAttribute($session->UserID, $attributes);
        }

        return $spam;
    }

    /**
     * Get a formatted warning message for spamming user.
     *
     * @param $type Type of record. Valid values are FloodControl::TYPE_*.
     * @return string The formatted warning message
     */
    public function getWarningMessage($type) {
        return sprintf(
                t('You have posted %1$s times within %2$s seconds. A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
                Gdn::config('Vanilla.'.$type.'.SpamCount', 1),
                Gdn::config('Vanilla.'.$type.'.SpamTime', 30),
                Gdn::config('Vanilla.'.$type.'.SpamLock', 60)
            );
    }
}
