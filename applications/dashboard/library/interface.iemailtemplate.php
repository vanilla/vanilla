<?php

/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

/**
 * Email Template Base Class
 *
 * @package Core
 * @since 2.3
 */
interface Gdn_IEmailTemplate {

    /**
     * Set message string
     *
     * @param string $message The HTML formatted email message (the body of the email).
     * @param bool $convertNewlines Whether to convert new lines to html br tags.
     * @return EmailTemplate $this The calling object.
     */
    public function setMessage($message, $convertNewlines = false);

    /**
     * Get message string
     *
     * @return string The HTML formatted email message (the body of the email).
     */
    public function getMessage();

    /**
     * Get plaintext setting
     *
     * @return bool Whether to render in plaintext.
     */
    public function isPlaintext();

    /**
     * Set format to plaintext
     *
     * @param bool $plainText Whether to render in plaintext.
     */
    public function setPlaintext($plainText);

    /**
     * Returns the formatted text of the email
     *
     * @return string
     */
    public function toString();

}
