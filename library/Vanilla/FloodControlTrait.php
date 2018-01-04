<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * Utility methods for models that want to implement flood control.
 *
 */
trait FloodControlTrait {
    /**
     * @var int Amount of post that can be created before the flood control kicks in.
     */
    private $postCountThreshold = 3;

    /**
     * @var int Time span, in seconds, during which posting will increase the postCount.
     */
    private $timeSpan = 30;

    /**
     * @var int Amount of time, in seconds, of a flood control block.
     */
    private $lockTime = 120;

    /**
     * @var bool Whether flood control is enabled or not.
     */
    private $floodControlEnabled = true;

    /**
     * @var string Key name, in the {@link CacheInterface}, of the current number of posts. Args:[__CLASS__, '$userID'].
     */
    private $keyCurrentPostCount;

    /**
     * @var string Key name, in the {@link CacheInterface}, of the last flood check. Args:[__CLASS__, '$userID'].
     */
    private $keyLastDateChecked;

    /**
     * @return int
     */
    public function getPostCountThreshold() {
        return $this->postCountThreshold;
    }

    /**
     * @param int $postCountThreshold
     * @return FloodControlTrait
     */
    public function setPostCountThreshold($postCountThreshold) {
        $this->postCountThreshold = $this->normalizeInput(__METHOD__, $postCountThreshold, 1);
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeSpan() {
        return $this->timeSpan;
    }

    /**
     * @param int $timeSpan >= 30
     * @return FloodControlTrait
     */
    public function setTimeSpan($timeSpan) {
        $this->timeSpan = $this->normalizeInput(__METHOD__, $timeSpan, 30);
        return $this;
    }

    /**
     * @return int
     */
    public function getLockTime() {
        return $this->lockTime;
    }

    /**
     * @param int $lockTime
     * @return FloodControlTrait
     */
    public function setLockTime($lockTime) {
        $this->lockTime = $this->normalizeInput(__METHOD__, $lockTime, 60);
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyCurrentPostCount() {
        return $this->keyCurrentPostCount ?: $this->getDefaultKeyCurrentPostCount();
    }

    /**
     * @return string
     */
    public function getDefaultKeyCurrentPostCount() {
        return 'floodcontrol.%s.%s.currentpostcount';
    }

    /**
     * @param string $keyCurrentPostCount
     * @return FloodControlTrait
     */
    public function setKeyCurrentPostCount($keyCurrentPostCount) {
        $this->keyCurrentPostCount = $keyCurrentPostCount;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeyLastDateChecked() {
        return $this->keyLastDateChecked ?: $this->getDefaultKeyLastDateChecked;
    }

    /**
     * @return string
     */
    public function getDefaultKeyLastDateChecked() {
        return 'floodcontrol.%s.%s.lastdatechecked';
    }

    /**
     * @param string $keyLastDateChecked
     * @return FloodControlTrait
     */
    public function setKeyLastDateChecked($keyLastDateChecked) {
        $this->keyLastDateChecked = $keyLastDateChecked;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFloodControlEnabled() {
        return $this->floodControlEnabled;
    }

    /**
     * @param bool $floodControlEnabled
     * @return FloodControlTrait
     */
    public function setFloodControlEnabled($floodControlEnabled) {
        $this->floodControlEnabled = $floodControlEnabled;
        return $this;
    }

    /**
     * Check if a user is spamming, based on the last call to this function and add a validation result.
     *
     * @param int $userID
     * @param CacheInterface $storageObject object in which we will store the floodcontrol data.
     * @return bool True if the user is spamming, false otherwise.
     */
    public function checkUserSpamming($userID, CacheInterface $storageObject) {
        if (!$this->isFloodControlEnabled()) {
            return false;
        }

        $userPostCountKey = vsprintf($this->getKeyCurrentPostCount(), [strtolower(__CLASS__), $userID]);
        $userLastDateCheckedKey = vsprintf($this->getKeyLastDateChecked(), [strtolower(__CLASS__), $userID]);

        $isSpamming = false;
        $countSpamCheck = $storageObject->get($userPostCountKey, 0);
        $dateSpamCheck = $storageObject->get($userLastDateCheckedKey, null);
        $secondsSinceSpamCheck = time() - (int)strtotime($dateSpamCheck);

        // Apply a spam lock if necessary
        $attributes = [];
        if ($dateSpamCheck !== null && $secondsSinceSpamCheck < $this->lockTime && $countSpamCheck >= $this->postCountThreshold) {
            $isSpamming = true;
            // Update the 'waiting period' every time they try to post again
            $attributes[$userLastDateCheckedKey] = date('Y-m-d H:i:s');
        } else {
            if ($secondsSinceSpamCheck > $this->timeSpan) {
                $attributes[$userPostCountKey] = 1;
                $attributes[$userLastDateCheckedKey] = date('Y-m-d H:i:s');
            } else {
                $attributes[$userPostCountKey] = $countSpamCheck + 1;
            }
        }

        $storageObject->setMultiple($attributes);

        if ($isSpamming && property_exists($this, 'Validation') && is_a($this->Validation, 'Gdn_Validation')) {
            $this->Validation->addValidationResult(
                'Body',
                '@'.$this->getFloodControlWarningMessage()
            );
        }

        return $isSpamming;
    }

    /**
     * Get a formatted warning message for spamming user.
     *
     * @return string The formatted warning message
     */
    protected function getFloodControlWarningMessage() {
        return sprintf(
                t('You have posted %1$s times within %2$s seconds. A spam block is now in effect on your account. You must wait at least %3$s seconds before attempting to post again.'),
                $this->postCountThreshold,
                $this->timeSpan,
                $this->lockTime
            );
    }

    /**
     * Takes a value and sets it to its minimum if not valid.
     *
     * @param string $caller Function name that called normalizeInput
     * @param int $value
     * @param int $minimum
     * @return int
     */
    private function normalizeInput($caller, $value, $minimum) {
        if (!ctype_digit((string)$value) || $minimum < 1) {
            trigger_error("$caller value '$value' was normalized to '$minimum' because it was invalid.", E_USER_NOTICE);
            $value = $minimum;
        }

        return (int)$value;
    }
}
