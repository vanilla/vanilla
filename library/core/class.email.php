<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Web\Exception\ServerException;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Email\MailerInterface;
use Vanilla\Email\SendGridMailer;
use Vanilla\Email\StandardMailer;
use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\DebugUtils;

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
class Gdn_Email extends Gdn_Pluggable implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const CONF_DISABLED = "Garden.Email.Disabled";

    public const CONF_SENDGRID_MAILER_API_KEY = "Garden.Email.SendGridMailer.ApiKey";

    public const CONF_SENDGRID_MAILER_ENABLED = "Garden.Email.SendGridMailer.Enabled";

    /** Error: The email was not attempted to be sent.. */
    const ERR_SKIPPED = 1;

    /** @var bool */
    private $debug;

    /**
     * @deprecated
     * @var PHPMailer
     */
    public $PhpMailer;

    protected MailerInterface $mailer;

    /** @var boolean */
    private $_IsToSet;

    /** @var array Recipients that were skipped because they lack permission. */
    public $Skipped = [];

    /** @var EmailTemplate The email body renderer. Use this to edit the email body. */
    protected $emailTemplate;

    /** @var string The format of the email. */
    protected $format;

    protected ConfigurationInterface $config;

    /** @var string[] The supported email formats. */
    public static $supportedFormats = ["html", "text"];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->PhpMailer = StandardMailer::createPhpMailer();
        $this->config = \Gdn::config();
        $this->mailer = $this->initializeMailer();

        $this->clear();
        $this->addHeader("Precedence", "list");
        $this->addHeader("X-Auto-Response-Suppress", "All");
        $this->setEmailTemplate(new EmailTemplate());

        // Default debug status to the site config.
        $this->setDebug((bool) c("Garden.Email.Debug"));

        // This class is largely instantiated at the usage site, not the container, so we can't rely on it to wire up the dependency.
        $this->setLogger(Logger::getLogger());

        $this->resolveFormat();
        parent::__construct();
    }

    /**
     * Initialize and return appropriate mailer instance based on current configuration.
     *
     * @return MailerInterface
     */
    private function initializeMailer(): MailerInterface
    {
        $addonManager = Gdn::addonManager();
        if (
            $addonManager->isEnabled("vanilla-queue", \Vanilla\Addon::TYPE_ADDON) &&
            !$addonManager->isEnabled("vanillapop", \Vanilla\Addon::TYPE_ADDON) &&
            $this->config->configKeyExists(self::CONF_SENDGRID_MAILER_API_KEY) &&
            $this->config->get(self::CONF_SENDGRID_MAILER_ENABLED)
        ) {
            return new SendGridMailer();
        }
        return new StandardMailer($this->PhpMailer);
    }

    /**
     * Sets the format property based on the config, and defaults to html.
     */
    protected function resolveFormat()
    {
        $configFormat = c("Garden.Email.Format", "text");
        if (in_array(strtolower($configFormat), self::$supportedFormats)) {
            $this->setFormat($configFormat);
        } else {
            $this->setFormat("text");
        }
    }

    /**
     * Sets the format property, the email mime type and the email template format property.
     *
     * @param string $format The format of the email. Must be in the $supportedFormats array.
     * @return Gdn_Email
     */
    public function setFormat($format)
    {
        if (strtolower($format) === "html") {
            $this->format = "html";
            $this->mimeType("text/html");
            $this->emailTemplate->setPlaintext(false);
        } else {
            $this->format = "text";
            $this->mimeType("text/plain");
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
    public function getFormat()
    {
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
    public function addHeader($name, $value)
    {
        $this->mailer->addHeader($name, $value);
        return $this;
    }

    /**
     * Schedule a particular delivery time for this email.
     *
     * This only works with sendgrid the time may not be more than 3 days in the future.
     */
    public function scheduleDelivery(\DateTimeInterface $time): void
    {
        $nowTs = CurrentTimeStamp::get();
        $deliveryTs = $time->getTimestamp();

        $threeDayDiff = 60 * 60 * 24 * 3;
        if ($deliveryTs - $nowTs > $threeDayDiff) {
            throw new ServerException("You can't schedule an email for delivery more than 3 days in the future.", 500, [
                "scheduleTime" => $time,
            ]);
        }

        $this->addHeader("X-SMTPAPI", json_encode(["send_at" => $deliveryTs]));
    }

    /**
     * Adds to the "Bcc" recipient collection.
     *
     * @param mixed $recipientEmail An email (or array of emails) to add to the "Bcc" recipient collection.
     * @param string $recipientName The recipient name associated with $recipientEmail. If $recipientEmail is
     * an array of email addresses, this value will be ignored.
     * @return Gdn_Email
     */
    public function bcc($recipientEmail, $recipientName = "")
    {
        if ($recipientName != "" && c("Garden.Email.OmitToName", false)) {
            $recipientName = "";
        }

        ob_start();
        $this->mailer->addBCC($recipientEmail, $recipientName);
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
    public function cc($recipientEmail, $recipientName = "")
    {
        if ($recipientName != "" && c("Garden.Email.OmitToName", false)) {
            $recipientName = "";
        }

        ob_start();
        $this->mailer->addCC($recipientEmail, $recipientName);
        ob_end_clean();
        return $this;
    }

    /**
     * Clears out all previously specified values for this object and restores
     * it to the state it was in when it was instantiated.
     *
     * @return Gdn_Email
     */
    public function clear()
    {
        $this->mailer->clearRecipients();
        $this->mailer->clearContent();
        $this->from();
        $this->_IsToSet = false;
        $this->mimeType(c("Garden.Email.MimeType", "text/plain"));
        $this->_MasterView = "email.master";
        $this->Skipped = [];
        return $this;
    }

    /**
     * Get the site's default from address.
     *
     * @return string
     */
    public function getDefaultFromAddress(): string
    {
        $result = c("Garden.Email.SupportAddress", "");
        if (!$result) {
            $result = $this->getNoReplyAddress();
        }
        return $result;
    }

    /**
     * Get the site's default sender (smtp envelope) address.
     *
     * @return string
     */
    public function getDefaultSenderAddress(): string
    {
        $result = c("Garden.Email.EnvelopeAddress", "");
        if (!$result) {
            $result = $this->getDefaultFromAddress();
        }
        return $result;
    }

    /**
     * Get an address suitable for no-reply-style emails.
     *
     * @return string
     */
    public function getNoReplyAddress(): string
    {
        $host = Gdn::request()->host();
        $result = "noreply@{$host}";
        return $result;
    }

    /**
     * Allows the explicit definition of the email's sender address & name.
     * Defaults to the applications Configuration 'SupportEmail' & 'SupportName' settings respectively.
     *
     * @param string $fromEmail
     * @param string $fromName
     * @param boolean $bOverrideSender optional. default false.
     * @return Gdn_Email
     */
    public function from($fromEmail = "", $fromName = "", $bOverrideSender = false)
    {
        if ($fromEmail == "") {
            $fromEmail = $this->getDefaultFromAddress();
        }

        if ($fromName == "") {
            $fromName = c("Garden.Email.SupportName", c("Garden.Title", ""));
        }

        if ($this->mailer->getSender() == "" || $bOverrideSender) {
            $envelopeEmail = $bOverrideSender ? $fromEmail : $this->getDefaultSenderAddress();
            $this->mailer->setSender($envelopeEmail);
        }

        ob_start();
        $this->mailer->setFrom($fromEmail, $fromName);
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
    public function masterView($masterView)
    {
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
    public function message($message, $convertNewlines = true, $filter = true)
    {
        $this->emailTemplate->setMessage($message, $convertNewlines, $filter);
        return $this;
    }

    public function formatMessage($message)
    {
        if ($this->mailer->getContentType() == "text/html") {
            $textVersion = false;
            if (stristr($message, "<!-- //TEXT VERSION FOLLOWS//")) {
                $emailParts = explode("<!-- //TEXT VERSION FOLLOWS//", $message);
                $textVersion = array_pop($emailParts);
                $message = array_shift($emailParts);
                $textVersion = trim(
                    strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', "", $textVersion))
                );
                $message = trim($message);
            }

            $this->mailer->setHtmlContent(self::imageInlineStyles($message));
            if (!empty($textVersion)) {
                $textVersion = html_entity_decode($textVersion);
                $this->mailer->setTextContent($textVersion);
            }
        } else {
            $this->mailer->setTextOnlyContent($message);
        }
        return $this;
    }

    /**
     * @return EmailTemplate The email body renderer.
     */
    public function getEmailTemplate()
    {
        return $this->emailTemplate;
    }

    /**
     * @param EmailTemplate $emailTemplate The email body renderer.
     * @return Gdn_Email
     */
    public function setEmailTemplate($emailTemplate)
    {
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
    public static function getTextVersion($template)
    {
        if (stristr($template, "<!-- //TEXT VERSION FOLLOWS//")) {
            $emailParts = explode("<!-- //TEXT VERSION FOLLOWS//", $template);
            $textVersion = array_pop($emailParts);
            $textVersion = trim(
                strip_tags(preg_replace('/<(head|title|style|script)[^>]*>.*?<\/\\1>/s', "", $textVersion))
            );
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
    public static function getHTMLVersion($template)
    {
        if (stristr($template, "<!-- //TEXT VERSION FOLLOWS//")) {
            $emailParts = explode("<!-- //TEXT VERSION FOLLOWS//", $template);
            array_pop($emailParts);
            $message = array_shift($emailParts);
            $message = trim($message);
            return $message;
        }
        return $template;
    }

    /**
     * Replace image classes with inline styles for proper layout in email
     *
     * @param $message
     * @return mixed|string
     */
    public static function imageInlineStyles($message)
    {
        // The patterns to search for as an array
        $patterns = [];
        // The string to replace the corresponding pattern index
        $replacements = [];

        // replace the class embedImage-img with inline styles
        $patterns[] = '/class="embedImage-img"/';
        $replacements[] =
            'style="height: auto; display: inline-flex; position: relative; margin-left: auto; margin-right: auto; max-width: 100%; max-height: 100%;"';

        // replace the class embedImage-link with inline styles
        $patterns[] = '/class="embedImage-link"/';
        $replacements[] = 'style="display: inline-flex; flex-direction: column;"';

        $embedImageStyle = "width: 100%; display: block;";
        // the different size options and styles
        $displayStyles = [
            "small" => "max-width: calc(33.333% - 1px);",
            "medium" => "max-width: calc(66.666% - 1px);",
            "large" => "max-width: 100%",
        ];
        // the different float options and styles
        $floatStyles = [
            "center" => "float: none; margin-top: 16px;",
            "left" => "float: left; margin-right: 20px; margin-bottom: 16px;",
            "right" => "float: right; margin-left: 20px; margin-bottom: 16px;",
        ];

        // add the various combination possibilities to the patterns and replacements
        foreach ($displayStyles as $displayKey => $displayStyle) {
            $displayClass = "display-" . $displayKey;

            foreach ($floatStyles as $floatKey => $floatStyle) {
                $floatClass = "float-" . $floatKey;

                $patterns[] = '/class="embedExternal embedImage ' . $displayClass . " " . $floatClass . '"/';
                $replacements[] = 'style="width: 100%; display: block; ' . $displayStyle . " " . $floatStyle . '"';
            }
        }

        return preg_replace($patterns, $replacements, $message);
    }

    /**
     * Sets the mime-type of the email.
     *
     * Only accept text/plain or text/html.
     *
     * @param string $mimeType The mime-type of the email.
     * @return Gdn_Email
     */
    public function mimeType($mimeType)
    {
        $this->mailer->setContentType($mimeType);
        return $this;
    }

    /**
     * Send the email.
     *
     * @param string $eventName
     * @return boolean
     * @throws \Exception Throws an exception if emailing is disabled.
     * @throws \PHPMailer\PHPMailer\Exception Throws an exception if there is a problem sending the email.
     */
    public function send($eventName = "")
    {
        $this->formatMessage($this->emailTemplate->toString());
        $this->fireAs(Gdn_Email::class)->fireEvent("BeforeSendMail");

        if (c("Garden.Email.Disabled")) {
            throw new Exception("Email disabled", self::ERR_SKIPPED);
        }

        if (DebugUtils::isTestMode()) {
            // Don't actually send emails in tests.
            return true;
        }

        if ($eventName != "") {
            $this->EventArguments["EventName"] = $eventName;
            $this->fireEvent("SendMail");
        }

        if (!empty($this->Skipped) && count($this->mailer->getAllRecipients()) == 0) {
            // We've skipped all recipients.
            throw new Exception("No valid email recipients.", self::ERR_SKIPPED);
        }

        $this->mailer->send();

        if ($this->isDebug() && $this->logger instanceof Psr\Log\LoggerInterface) {
            $this->logger->info("Email Payload", [
                Vanilla\Logger::FIELD_CHANNEL => Vanilla\Logger::CHANNEL_SYSTEM,
                "event" => "email_sent",
                "timestamp" => time(),
                "userid" => Gdn::session()->UserID,
                "username" => Gdn::session()->User->Name ?? "anonymous",
                "ip" => Gdn::request()->ipAddress(),
                "method" => Gdn::request()->requestMethod(),
                "domain" => rtrim(url("/", true), "/"),
                "path" => Gdn::request()->path(),
                "charset" => $this->mailer->getCharSet(),
                "contentType" => $this->mailer->getContentType(),
                "from" => $this->mailer->getFromAddress(),
                "fromName" => $this->mailer->getFromName(),
                "sender" => $this->mailer->getSender(),
                "subject" => $this->mailer->getSubject(),
                "to" => array_column($this->mailer->getToAddresses(), "email"),
                "cc" => array_column($this->mailer->getCcAddresses(), "email"),
                "bcc" => array_column($this->mailer->getBccAddresses(), "email"),
            ]);
        }
        return true;
    }

    /**
     * Adds subject of the message to the email.
     *
     * @param string $subject The subject of the message.
     * @return Gdn_Email
     */
    public function subject($subject)
    {
        $this->mailer->setSubject($subject);
        return $this;
    }

    public function addTo($recipientEmail, $recipientName = "")
    {
        if ($recipientName != "" && c("Garden.Email.OmitToName", false)) {
            $recipientName = "";
        }

        ob_start();
        $this->mailer->addTo($recipientEmail, $recipientName);
        ob_end_clean();
        return $this;
    }

    /**
     * Adds to the "To" recipient collection.
     *
     * @param mixed $recipientEmail An email, an array of emails, or a user object to add to the "To" recipient collection.
     *   Note: Passing a user object adds Garden.Email.View permission checks.
     * @param string $recipientName The recipient name associated with $recipientEmail. If $recipientEmail is
     *   an array of email addresses, this value will be ignored.
     * @return Gdn_Email
     */
    public function to($recipientEmail, $recipientName = "")
    {
        if ($recipientName != "" && c("Garden.Email.OmitToName", false)) {
            $recipientName = "";
        }

        if (is_string($recipientEmail)) {
            if (strpos($recipientEmail, ",") > 0) {
                $recipientEmail = explode(",", $recipientEmail);
                // trim no need, PhpMailer::addAnAddress() will do it
                return $this->to($recipientEmail, $recipientName);
            }
            if ($this->mailer->isSingleTo()) {
                return $this->addTo($recipientEmail, $recipientName);
            }
            if (!$this->_IsToSet) {
                $this->_IsToSet = true;
                $this->addTo($recipientEmail, $recipientName);
            } else {
                $this->cc($recipientEmail, $recipientName);
            }
            return $this;
        } elseif (
            (is_object($recipientEmail) && property_exists($recipientEmail, "Email")) ||
            (is_array($recipientEmail) && isset($recipientEmail["Email"]))
        ) {
            $user = $recipientEmail;
            $recipientName = val("Name", $user);
            $recipientEmail = val("Email", $user);
            $userID = val("UserID", $user, false);

            if ($userID !== false) {
                // Check to make sure the user can receive email.
                if (!Gdn::userModel()->checkPermission($userID, "Garden.Email.View")) {
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
                $recipientName = array_fill(0, $count, "");
            }
            if ($count == count($recipientName)) {
                $recipientEmail = array_combine($recipientEmail, $recipientName);
                foreach ($recipientEmail as $email => $name) {
                    $this->to($email, $name);
                }
            } else {
                trigger_error(errorMessage("Size of arrays do not match", "Email", "To"), E_USER_ERROR);
            }

            return $this;
        }

        trigger_error(
            errorMessage(
                "Incorrect first parameter (" . gettype($recipientEmail) . ") passed to function.",
                "Email",
                "To"
            ),
            E_USER_ERROR
        );
    }

    /**
     * Should mailing be debugged?
     *
     * @param boolean $debug
     */
    public function setDebug(bool $debug)
    {
        $this->debug = $debug;
    }

    /**
     * Is mailing being debugged?
     *
     * @return boolean
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Get email footer html string if available
     *
     * @return string
     */
    public function getFooterContent(string $content = null): string
    {
        $footerConfig = $content ?? $this->config->get("Garden.Email.Footer", "");
        if (empty(trim($footerConfig))) {
            return "";
        }
        try {
            $footerText = $this->formatConfigContent($footerConfig);
        } catch (\Exception $exception) {
            ErrorLogger::error(
                "Failed to render email footer.",
                ["email"],
                [
                    "exception" => $exception,
                ]
            );
            $footerText = "";
        }

        return $footerText;
    }

    /**
     * Covert config content to html or plain text
     *
     * @param string $content
     * @return string
     */
    protected function formatConfigContent(string $content): string
    {
        $rich2Formatter = Gdn::getContainer()->get(Rich2Format::class);

        if ($this->format === "html") {
            $formattedContent = $rich2Formatter->renderHTML($content);
        } else {
            $formattedContent = $rich2Formatter->renderPlainText($content);
        }
        if (str_contains($formattedContent, Rich2Format::RENDER_ERROR_MESSAGE)) {
            return "";
        }
        return $formattedContent;
    }

    /**
     * Sets an activity ID if this email is associated with an `Activity` record.
     * This is so that mailers are able to update the associated activity email status.
     *
     * @param int $activityID
     * @return void
     */
    public function setActivityID(int $activityID): void
    {
        $this->mailer->setActivityID($activityID);
    }

    /**
     * Get the MailerInterface instance.
     *
     * @return MailerInterface
     */
    public function getMailer(): MailerInterface
    {
        return $this->mailer;
    }
}
