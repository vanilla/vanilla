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
     * @inheritDoc
     */
    public function addHeader(string $name, ?string $value = null): void
    {
        $this->PHPMailer->addCustomHeader($name, $value);
    }

    /**
     * @inheritDoc
     */
    public function addTo(string $recipientMail, ?string $recipientName = null): void
    {
        $this->PHPMailer->addAddress($recipientMail, $recipientName);
    }

    /**
     * @inheritDoc
     */
    public function addCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->PHPMailer->addCC($recipientMail, $recipientName);
    }

    /**
     * @inheritDoc
     */
    public function addBCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->PHPMailer->addBCC($recipientMail, $recipientName);
    }

    /**
     * @inheritDoc
     */
    public function setFrom(string $senderEmail, ?string $senderName = null): void
    {
        $this->PHPMailer->setFrom($senderEmail, $senderName, false);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getFromAddress(): string
    {
        return $this->PHPMailer->From;
    }

    /**
     * @inheritDoc
     */
    public function getFromName(): string
    {
        return $this->PHPMailer->FromName;
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return $this->PHPMailer->Subject;
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return $this->PHPMailer->ContentType;
    }

    /**
     * @inheritDoc
     */
    public function setContentType(string $type): void
    {
        $this->PHPMailer->isHTML($type === self::CONTENT_TYPE_HTML);
    }

    /**
     * @inheritDoc
     */
    public function getCharSet(): ?string
    {
        return $this->PHPMailer->CharSet;
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject, bool $encode = true): void
    {
        if ($encode) {
            $subject = mb_encode_mimeheader($subject, $this->PHPMailer->CharSet);
        }
        $this->PHPMailer->Subject = $subject;
    }

    /**
     * @inheritDoc
     */
    public function isSingleTo(): bool
    {
        return $this->PHPMailer->SingleTo;
    }

    /**
     * @inheritDoc
     */
    public function clearRecipients(): void
    {
        $this->PHPMailer->clearAllRecipients();
    }

    /**
     * @inheritDoc
     */
    public function clearContent(): void
    {
        $this->PHPMailer->Body = "";
        $this->PHPMailer->AltBody = "";
    }

    /**
     * @inheritDoc
     */
    public function setTextContent(string $content = ""): void
    {
        $this->PHPMailer->AltBody = $content;
    }

    /**
     * @inheritDoc
     */
    public function getTextContent(): ?string
    {
        return $this->PHPMailer->AltBody;
    }

    /**
     * @inheritDoc
     */
    public function setHtmlContent(string $content = ""): void
    {
        $this->PHPMailer->msgHTML($content);
    }

    /**
     * @inheritDoc
     */
    public function setTextOnlyContent(string $content = ""): void
    {
        $this->PHPMailer->Body = $content;
    }

    /**
     * @inheritDoc
     */
    public function getTextOnlyContent(): ?string
    {
        return $this->PHPMailer->Body;
    }

    /**
     * @inheritDoc
     */
    public function getSender(): ?string
    {
        return $this->PHPMailer->Sender;
    }

    /**
     * @inheritDoc
     */
    public function setSender(string $senderEmail): void
    {
        $this->PHPMailer->Sender = $senderEmail;
    }

    /**
     * @inheritDoc
     */
    public function getAllRecipients(): array
    {
        return $this->PHPMailer->getAllRecipientAddresses();
    }

    /**
     * @inheritDoc
     */
    public function setBodyContent(string $content = ""): void
    {
        $this->PHPMailer->Body = $content;
    }

    /**
     * @inheritDoc
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
