<?php

namespace Vanilla\Email;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Vanilla\VanillaMailer;

class StandardMailer implements MailerInterface
{
    private PHPMailer $PHPMailer;

    private ?int $activityID = null;

    public function __construct(?VanillaMailer $PHPMailer = null)
    {
        if (!isset($PHPMailer)) {
            $PHPMailer = self::createPhpMailer();
        }
        $this->PHPMailer = $PHPMailer;
    }

    public static function createPhpMailer(): VanillaMailer
    {
        $phpMailer = new VanillaMailer();
        $phpMailer->CharSet = "utf-8";
        $phpMailer->SingleTo = c("Garden.Email.SingleTo", false);
        $phpMailer->Hostname = c("Garden.Email.Hostname", "");
        $phpMailer->Encoding = "quoted-printable";
        $phpMailer->Timeout = 5;
        return $phpMailer;
    }

    /**
     * @inheritdoc
     */
    public function addHeader(string $name, ?string $value = null): void
    {
        $this->PHPMailer->addCustomHeader($name, $value);
    }

    /**
     * @inheritdoc
     */
    public function addTo(string $recipientMail, ?string $recipientName = null): void
    {
        $this->PHPMailer->addAddress($recipientMail, $recipientName);
    }

    /**
     * @inheritdoc
     */
    public function addCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->PHPMailer->addCC($recipientMail, $recipientName);
    }

    /**
     * @inheritdoc
     */
    public function addBCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->PHPMailer->addBCC($recipientMail, $recipientName);
    }

    /**
     * @inheritdoc
     */
    public function setFrom(string $senderEmail, ?string $senderName = null): void
    {
        $this->PHPMailer->setFrom($senderEmail, $senderName, false);
    }

    /**
     * @inheritdoc
     */
    public function send(): bool
    {
        if (c("Garden.Email.UseSmtp")) {
            $this->PHPMailer->isSMTP();
            $this->configureSMTP();
        } else {
            $this->PHPMailer->isMail();
        }

        $this->PHPMailer->setThrowExceptions(true);

        if (!$this->PHPMailer->send()) {
            throw new \Exception($this->PHPMailer->ErrorInfo);
        }
        if (isset($this->activityID)) {
            $activityModel = \Gdn::getContainer()->get(\ActivityModel::class);
            $activityModel->update(["Emailed" => \ActivityModel::SENT_OK], ["ActivityID" => $this->activityID]);
        }

        return true;
    }

    /**
     * Configure Smtp settings for email
     *
     * @return void
     */
    private function configureSMTP(): void
    {
        $smtpHost = c("Garden.Email.SmtpHost", "");
        $smtpPort = c("Garden.Email.SmtpPort", 25);
        if (str_contains($smtpHost, ":")) {
            [$smtpHost, $smtpPort] = explode(":", $smtpHost);
        }

        $this->PHPMailer->Host = $smtpHost;
        $this->PHPMailer->Port = $smtpPort;
        $this->PHPMailer->SMTPSecure = c("Garden.Email.SmtpSecurity", "");
        $this->PHPMailer->Username = $username = c("Garden.Email.SmtpUser", "");
        $this->PHPMailer->Password = $password = c("Garden.Email.SmtpPassword", "");
        if (!empty($username)) {
            $this->PHPMailer->SMTPAuth = true;
        }
    }

    /**
     * @inheritdoc
     */
    public function getFromAddress(): string
    {
        return $this->PHPMailer->From;
    }

    /**
     * @inheritdoc
     */
    public function getFromName(): string
    {
        return $this->PHPMailer->FromName;
    }

    /**
     * @inheritdoc
     */
    public function getToAddresses(): array
    {
        $result = [];
        foreach ($this->PHPMailer->getToAddresses() as $toAddress) {
            [$email, $name] = $toAddress;
            $result[] = ["email" => $email, "name" => $name];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getCcAddresses(): array
    {
        $result = [];
        foreach ($this->PHPMailer->getCcAddresses() as $ccAddress) {
            [$email, $name] = $ccAddress;
            $result[] = ["email" => $email, "name" => $name];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getBccAddresses(): array
    {
        $result = [];
        foreach ($this->PHPMailer->getBccAddresses() as $bccAddress) {
            [$email, $name] = $bccAddress;
            $result[] = ["email" => $email, "name" => $name];
        }
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getSubject(): string
    {
        return $this->PHPMailer->Subject;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return $this->PHPMailer->ContentType;
    }

    /**
     * @inheritdoc
     */
    public function setContentType(string $type): void
    {
        $this->PHPMailer->isHTML($type === self::CONTENT_TYPE_HTML);
    }

    /**
     * @inheritdoc
     */
    public function getCharSet(): ?string
    {
        return $this->PHPMailer->CharSet;
    }

    /**
     * @inheritdoc
     */
    public function setSubject(string $subject, bool $encode = true): void
    {
        if ($encode) {
            $subject = mb_encode_mimeheader($subject, $this->PHPMailer->CharSet);
        }
        $this->PHPMailer->Subject = $subject;
    }

    /**
     * @inheritdoc
     */
    public function isSingleTo(): bool
    {
        return $this->PHPMailer->SingleTo;
    }

    /**
     * @inheritdoc
     */
    public function clearRecipients(): void
    {
        $this->PHPMailer->clearAllRecipients();
    }

    /**
     * @inheritdoc
     */
    public function clearContent(): void
    {
        $this->PHPMailer->Body = "";
        $this->PHPMailer->AltBody = "";
    }

    /**
     * @inheritdoc
     */
    public function setTextContent(string $content = ""): void
    {
        $this->PHPMailer->AltBody = $content;
    }

    /**
     * @inheritdoc
     */
    public function getTextContent(): ?string
    {
        return $this->PHPMailer->AltBody;
    }

    /**
     * @inheritdoc
     */
    public function setHtmlContent(string $content = ""): void
    {
        $this->PHPMailer->msgHTML($content);
    }

    /**
     * @inheritdoc
     */
    public function setTextOnlyContent(string $content = ""): void
    {
        $this->PHPMailer->Body = $content;
    }

    /**
     * @inheritdoc
     */
    public function getTextOnlyContent(): ?string
    {
        return $this->PHPMailer->Body;
    }

    /**
     * @inheritdoc
     */
    public function getSender(): ?string
    {
        return $this->PHPMailer->Sender;
    }

    /**
     * @inheritdoc
     */
    public function setSender(string $senderEmail): void
    {
        $this->PHPMailer->Sender = $senderEmail;
    }

    /**
     * @inheritdoc
     */
    public function getAllRecipients(): array
    {
        return $this->PHPMailer->getAllRecipientAddresses();
    }

    /**
     * @inheritdoc
     */
    public function setBodyContent(string $content = ""): void
    {
        $this->PHPMailer->Body = $content;
    }

    /**
     * @inheritdoc
     */
    public function getBodyContent(): ?string
    {
        return $this->PHPMailer->Body;
    }

    public function setActivityID(int $activityID): void
    {
        $this->activityID = $activityID;
    }
}
