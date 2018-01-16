<?php

/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Class RawEmailTemplate
 *
 * Allows sending regular HTML emails without invoking controller rendering.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @package Core
 * @since 2.2
 */
class RawEmailTemplate extends Gdn_Pluggable implements Gdn_IEmailTemplate {

    /**
     * Delimiter for plaintext email.
     */
    const PLAINTEXT_START = '<!-- //TEXT VERSION FOLLOWS//';

    /**
     * @return bool Whether to render in plaintext.
     */
    public function isPlaintext() {
        return $this->plaintext;
    }

    /**
     * @param bool $plainText Whether to render in plaintext.
     */
    public function setPlaintext($plainText) {
        $this->plaintext = $plainText;
    }

    /**
     * @return string The HTML formatted email message (the body of the email).
     */
    public function getMessage() {
        return $this->message;
    }

    /**
     * @param string $message The HTML formatted email message (the body of the email).
     * @param bool $convertNewlines Whether to convert new lines to html br tags.
     * @param bool $filter Whether to filter HTML or not.
     * @return EmailTemplate $this The calling object.
     */
    public function setMessage($message, $convertNewlines = false, $filter = false) {
        $this->message = $this->formatContent($message, $convertNewlines, $filter);
        return $this;
    }

    /**
     * Filters an unsafe HTML string and returns it.
     *
     * @param string $html The HTML to filter.
     * @param bool $convertNewlines Whether to convert new lines to html br tags.
     * @param bool $filter Whether to filter HTML or not.
     * @return string The filtered HTML string.
     */
    protected function formatContent($html, $convertNewlines = false, $filter = false) {
        $str = $html;
        if ($filter) {
            $str = Gdn_Format::htmlFilter($str);
        }
        if ($convertNewlines) {
            $str = preg_replace('/(\015\012)|(\015)|(\012)/', '<br>', $str);
        }
        // $str = strip_tags($str, ['b', 'i', 'p', 'strong', 'em', 'br']);
        return $str;
    }

    /**
     * Renders a plaintext email.
     *
     * @return string A plaintext email.
     */
    protected function plainTextEmail() {
        return Gdn_Format::plainText(Gdn_Format::text($this->getMessage()));
    }

    /**
     * Render the email.
     *
     * @return string The rendered email.
     */
    public function toString() {
        if ($this->isPlaintext()) {
            return $this->plainTextEmail();
        }

        // Get message
        $message = $this->getMessage();

        // Append plaintext version
        $message .= self::PLAINTEXT_START.$this->plainTextEmail();
        return $message;
    }
}