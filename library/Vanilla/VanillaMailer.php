<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


use Vanilla\Utility\CamelCaseScheme;

/**
 * Shim class used for backward compatibility.
 */
class VanillaMailer extends \PHPMailer {

    /**
     * Either set or get the value of "throwExceptions".
     *
     * @param bool $newValue Whether this instance should throw exceptions or not
     * @return The current value
     */
    public function throwExceptions($newValue = null) {
        deprecated('throwExceptions', 'getThrowExceptions/setThrowExceptions');
        if ($newValue !== null) {
            $this->exceptions = $newValue;
        }
        return $this->exceptions;
    }

    /**
     * Get throwExceptions value.
     *
     * @return bool Is this instance set to throw exceptions or not.
     */
    public function getThrowExceptions() {
        return $this->exceptions;
    }

    /**
     * Set throwExceptions value.
     *
     * @param bool $newValue The new value to set.
     * @return VanillaMailer
     */
    public function setThrowExceptions($newValue) {
        $this->exceptions = (bool)$newValue;

        return $this;
    }

    /**
     * Return the number of recipients.
     *
     * @return int
     */
    public function countRecipients() {
        deprecated('countRecipients', 'count($phpMailer->getAllRecipientAddresses())');
        return count($this->getAllRecipientAddresses());
    }

    /**
     * Check the phpmailerException message and tell us if the exception should be treated as
     * a server error instead of a "critical" error.
     * Server error means that we can try to resend the email.
     */
    public function isServerError(\phpmailerException $e) {
        $serverErrorMessages = [
            'connect_host',
            'data_not_accepted',
            'smtp_connect_failed',
            'execute',
        ];

        foreach($serverErrorMessages as $errorMessage) {
           if (strpos($e->getMessage(), $this->lang($errorMessage)) !== false) {
               return true;
           }
        }

        return false;
    }
}
