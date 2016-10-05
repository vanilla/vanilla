<?php
/**
 * @author Alexandre (DaazKu) Chouinard <alexandre.c@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


use Vanilla\Utility\CamelCaseScheme;

/**
 *
 */
class VanillaMailer extends \PHPMailer {

    public function throwExceptions($newValue = null) {
        if ($newValue !== null) {
            $this->exceptions = $newValue;
        }
        return $this->exceptions;
    }

    /**
     * Return the number of
     *
     * @return int
     */
    public function countRecipients() {
        deprecated('countRecipients', 'count($phpMailer->getAllRecipientAddresses())');
        return count($this->getAllRecipientAddresses());
    }
}
