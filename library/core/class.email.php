<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

/**
 * Email layer abstraction
 *
 * This class implements fluid method chaining.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @package Core
 * @since 2.0
 */
class Gdn_Email extends Gdn_Pluggable {

    /** Error: The email was not attempted to be sent.. */
    const ERR_SKIPPED = 1;

    /** @var PHPMailer */
    public $PhpMailer;

    /** @var boolean */
    private $_IsToSet;

    /** @var array Recipients that were skipped because they lack permission. */
    public $Skipped = [];

    /** @var EmailTemplate The email body renderer. Use this to edit the email body. */
    protected $emailTemplate;

    /** @var string The format of the email. */
    protected $format;

    /** @var string The supported email formats. */
    public static $supportedFormats = ['html', 'text'];

    /**
     * Constructor.
     */
    function __construct() {
        $this->PhpMailer = new \Vanilla\VanillaMailer();
        $this->PhpMailer->CharSet = 'utf-8';
        $this->PhpMailer->SingleTo = c('Garden.Email.SingleTo', false);
        $this->PhpMailer->Hostname = c('Garden.Email.Hostname', '');
        $this->PhpMailer->Encoding = 'quoted-printable';
        $this->clear();
        $this->addHeader('Precedence', 'list');
        $this->addHeader('X-Auto-Response-Suppress', 'All');
        $this->setEmailTemplate(new EmailTemplate());

        $this->resolveFormat();
        parent::__construct();
    }

    /**
     * Sets the format property based on the config, and defaults to html.
     */
    protected function resolveFormat() {
        $configFormat = c('Garden.Email.Format', 'text');
        if (in_array(strtolower($configFormat), self::$supportedFormats)) {
            $this->setFormat($configFormat);
        } else {
            $this->setFormat('text');
        }
    }

    /**
     * Sets the format property, the email mime type and the email template format property.
     *
     * @param string $format The format of the email. Must be in the $supportedFormats array.
     * @return Gdn_Email
     */
    public function setFormat($format) {
        if (strtolower($format) === 'html') {
            $this->format = 'html';
            $this->mimeType('text/html');
            $this->emailTemplate->setPlaintext(false);
        } else {
            $this->format = 'text';
            $this->mimeType('text/plain');
            $this->emailTemplate->setPlaintext(true);
        }
        return $this;
    }

    /**
     * Get email format
     *
     * Returns 'text' or 'html'.
     *
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * Add a custom header to the outgoing email.
     *
     * @param string $name
     * @param string $value
     * @since 2.1
     * @return Gdn_Email
     */
    public function addHeader($name, $value) {
        $this->PhpMailer->addCustomHeader("$name:$value");
        return $this;
    }

    /**
     * Adds to the "Bcc" recipient collection.
     *
     * @param mixed $recipientEmail An email (or array of emails) to add to the "Bcc" recipient collection.
     * @param string $recipientName The recipient name associated with $recipientEmail. If $recipientEmail is
     * an array of email addresses, this value will be ignored.
     * @return Gdn_Email
     */
    public function bcc($recipientEmail, $recipientName = '') {
        if ($recipientName != '' && c('Garden.Email.OmitToName', false)) {
            $recipientName = '';
        }

        ob_start();
        $this->PhpMailer->addBCC($recipientEmail, $recipientName);
        ob_end_clean();
        return $this;
    }

    /**
     * Adds to the "Cc" recipient collection.
     *
     * @param mixed $recipientEmail An email (or array of emails) to add to the "Cc" recipient collection.
     * @param string $recipientName The recipient name associated with $recipientEmail. If $recipientEmail is
     * an array of email addresses, this value will be ignored.
     * @return Gdn_Email
     */
    public function cc($recipientEmail, $recipientName = '') {
        if ($recipientName != '' && c('Garden.Email.OmitToName', false)) {
            $recipientName = '';
        }

        ob_start();
        $this->PhpMailer->addCC($recipientEmail, $recipientName);
        ob_end_clean();
        return $this;
    }

    /**
     * Clears out all previously specified values for this object and restores
     * it to the state it was in when it was instantiated.
     *
     * @return Gdn_Email
     */
    public function clear() {
        $this->PhpMailer->clearAllRecipients();
        $this->PhpMailer->Body = '';
        $this->PhpMailer->AltBody = '';
        $this->from();
        $this->_IsToSet = false;
        $this->mimeType(c('Garden.Email.MimeType', 'text/plain'));
        $this->_MasterView = 'email.master';
        $this->Skipped = [];
        return $this;
    }

    /**
     * Allows the explicit definition of the email's sender address & name.
     * Defaults to the applications Configuration 'SupportEmail' & 'SupportName' settings respectively.
     *
     * @param string $senderEmail
     * @param string $senderName
     * @param boolean $bOverrideSender optional. default false.
     * @return Gdn_Email
     */
    public function from($senderEmail = '', $senderName = '', $bOverrideSender = false) {
        if ($senderEmail == '') {
            $senderEmail = c('Garden.Email.SupportAddress', '');
            if (!$senderEmail) {
                $senderEmail = 'noreply@'.Gdn::request()->host();
            }
        }

        if ($senderName == '') {
            $senderName = c('Garden.Email.SupportName', c('Garden.Title', ''));
        }

        if ($this->PhpMailer->Sender == '' || $bOverrideSender) {
            $this->PhpMailer->Sender = $senderEmail;
        }

        ob_start();
        $this->PhpMailer->setFrom($senderEmail, $senderName, false);
        ob_end_clean();
        return $this;
    }

    /**
     * Allows the definition of a masterview other than the default: "email.master".
     *
     * @deprecated since version 2.2
     * @param string $masterView
     * @return Gdn_Email
     */
    public function masterView($masterView) {
        deprecated(__METHOD__);
        return $this;
    }

    /**
     * The message to be sent.
     *
     * @param string $message The body of the message to be sent.
     * @param boolean $convertNewlines Optional. Convert newlines to br tags
     * @param boolean $filter Optional. Filter HTML.
     * @return Gdn_Email
     */
    public function message($message, $convertNewlines = true, $filter = true) {
        $this->emailTemplate->setMessage($message, $convertNewlines, $filter);
        return $this;
    }

    public function formatMessage($message) {
        // htmlspecialchars_decode is being used here to revert any specialchar escaping done by Gdn_Format::text()
        // which, untreated, would result in &#039; in the message in place of single quotes.

        if ($this->PhpMailer->ContentType == 'text/html') {
            $textVersion = false;
            if (stristr($message, '<!-- //TEXT VERSION FOLLOWS//')) {
                $emailParts = explode('<!-- //TEXT VERSION FOLLOWS//', $message);
                $textVersion = array_pop($emailParts);
                $message = array_shift($emailParts);
                $textVersion = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $textVersion)));
                $message = trim($message);
            }

            $this->PhpMailer->msgHTML(htmlspecialchars_decode($message, ENT_QUOTES));
            if ($textVersion !== false && !empty($textVersion)) {
                $textVersion = html_entity_decode($textVersion);
                $this->PhpMailer->AltBody = $textVersion;
            }
        } else {
            $this->PhpMailer->Body = htmlspecialchars_decode($message, ENT_QUOTES);
        }
        return $this;
    }

    /**
     * @return EmailTemplate The email body renderer.
     */
    public function getEmailTemplate() {
        return $this->emailTemplate;
    }

    /**
     * @param EmailTemplate $emailTemplate The email body renderer.
     * @return Gdn_Email
     */
    public function setEmailTemplate($emailTemplate) {
        $this->emailTemplate = $emailTemplate;

        // if we change email templates after construct, inform it of the current format
        if ($this->format) {
            $this->setFormat($this->format);
        }
        return $this;
    }

    /**
     *
     *
     * @param $template
     * @return bool|mixed|string
     */
    public static function getTextVersion($template) {
        if (stristr($template, '<!-- //TEXT VERSION FOLLOWS//')) {
            $emailParts = explode('<!-- //TEXT VERSION FOLLOWS//', $template);
            $textVersion = array_pop($emailParts);
            $textVersion = trim(strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', '', $textVersion)));
            return $textVersion;
        }
        return false;
    }

    /**
     *
     *
     * @param $template
     * @return mixed|string
     */
    public static function getHTMLVersion($template) {
        if (stristr($template, '<!-- //TEXT VERSION FOLLOWS//')) {
            $emailParts = explode('<!-- //TEXT VERSION FOLLOWS//', $template);
            array_pop($emailParts);
            $message = array_shift($emailParts);
            $message = trim($message);
            return $message;
        }
        return $template;
    }

    /**
     * Sets the mime-type of the email.
     *
     * Only accept text/plain or text/html.
     *
     * @param string $mimeType The mime-type of the email.
     * @return Gdn_Email
     */
    public function mimeType($mimeType) {
        $this->PhpMailer->isHTML($mimeType === 'text/html');
        return $this;
    }

    /**
     * @param string $eventName
     * @todo add port settings
     * @return boolean
     */
    public function send($eventName = '') {
        $this->formatMessage($this->emailTemplate->toString());
        $this->fireEvent('BeforeSendMail');

        if (c('Garden.Email.Disabled')) {
            throw new Exception('Email disabled', self::ERR_SKIPPED);
        }

        if (c('Garden.Email.UseSmtp')) {
            $this->PhpMailer->isSMTP();
            $smtpHost = c('Garden.Email.SmtpHost', '');
            $smtpPort = c('Garden.Email.SmtpPort', 25);
            if (strpos($smtpHost, ':') !== false) {
                list($smtpHost, $smtpPort) = explode(':', $smtpHost);
            }

            $this->PhpMailer->Host = $smtpHost;
            $this->PhpMailer->Port = $smtpPort;
            $this->PhpMailer->SMTPSecure = c('Garden.Email.SmtpSecurity', '');
            $this->PhpMailer->Username = $username = c('Garden.Email.SmtpUser', '');
            $this->PhpMailer->Password = $password = c('Garden.Email.SmtpPassword', '');
            if (!empty($username)) {
                $this->PhpMailer->SMTPAuth = true;
            }
        } else {
            $this->PhpMailer->isMail();
        }

        if ($eventName != '') {
            $this->EventArguments['EventName'] = $eventName;
            $this->fireEvent('SendMail');
        }

        if (!empty($this->Skipped) && count($this->PhpMailer->getAllRecipientAddresses()) == 0) {
            // We've skipped all recipients.
            throw new Exception('No valid email recipients.', self::ERR_SKIPPED);
        }

        $this->PhpMailer->setThrowExceptions(true);
        if (!$this->PhpMailer->send()) {
            throw new Exception($this->PhpMailer->ErrorInfo);
        }

        return true;
    }

    /**
     * Adds subject of the message to the email.
     *
     * @param string $subject The subject of the message.
     * @return Gdn_Email
     */
    public function subject($subject) {
        $this->PhpMailer->Subject = mb_encode_mimeheader($subject, $this->PhpMailer->CharSet);
        return $this;
    }

    public function addTo($recipientEmail, $recipientName = '') {
        if ($recipientName != '' && c('Garden.Email.OmitToName', false)) {
            $recipientName = '';
        }

        ob_start();
        $this->PhpMailer->addAddress($recipientEmail, $recipientName);
        ob_end_clean();
        return $this;
    }

    /**
     * Adds to the "To" recipient collection.
     *
     * @param mixed $recipientEmail An email (or array of emails) to add to the "To" recipient collection.
     * @param string $recipientName The recipient name associated with $recipientEmail. If $recipientEmail is
     *   an array of email addresses, this value will be ignored.
     * @return Gdn_Email
     */
    public function to($recipientEmail, $recipientName = '') {
        if ($recipientName != '' && c('Garden.Email.OmitToName', false)) {
            $recipientName = '';
        }

        if (is_string($recipientEmail)) {
            if (strpos($recipientEmail, ',') > 0) {
                $recipientEmail = explode(',', $recipientEmail);
                // trim no need, PhpMailer::addAnAddress() will do it
                return $this->to($recipientEmail, $recipientName);
            }
            if ($this->PhpMailer->SingleTo) {
                return $this->addTo($recipientEmail, $recipientName);
            }
            if (!$this->_IsToSet) {
                $this->_IsToSet = true;
                $this->addTo($recipientEmail, $recipientName);
            } else {
                $this->cc($recipientEmail, $recipientName);
            }
            return $this;

        } elseif ((is_object($recipientEmail) && property_exists($recipientEmail, 'Email'))
            || (is_array($recipientEmail) && isset($recipientEmail['Email']))
        ) {
            $user = $recipientEmail;
            $recipientName = val('Name', $user);
            $recipientEmail = val('Email', $user);
            $userID = val('UserID', $user, false);

            if ($userID !== false) {
                // Check to make sure the user can receive email.
                if (!Gdn::userModel()->checkPermission($userID, 'Garden.Email.View')) {
                    $this->Skipped[] = $user;

                    return $this;
                }
            }

            return $this->to($recipientEmail, $recipientName);

        } elseif ($recipientEmail instanceof Gdn_DataSet) {
            foreach ($recipientEmail->resultObject() as $object) {
                $this->to($object);
            }
            return $this;

        } elseif (is_array($recipientEmail)) {
            $count = count($recipientEmail);
            if (!is_array($recipientName)) {
                $recipientName = array_fill(0, $count, '');
            }
            if ($count == count($recipientName)) {
                $recipientEmail = array_combine($recipientEmail, $recipientName);
                foreach ($recipientEmail as $email => $name) {
                    $this->to($email, $name);
                }
            } else {
                trigger_error(errorMessage('Size of arrays do not match', 'Email', 'To'), E_USER_ERROR);
            }

            return $this;
        }

        trigger_error(errorMessage('Incorrect first parameter ('.gettype($recipientEmail).') passed to function.', 'Email', 'To'), E_USER_ERROR);
    }

    public function charset($use = '') {
        if ($use != '') {
            $this->PhpMailer->CharSet = $use;
            return $this;
        }
        return $this->PhpMailer->CharSet;
    }
}
