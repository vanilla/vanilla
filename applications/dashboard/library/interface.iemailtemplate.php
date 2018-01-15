<?php

/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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