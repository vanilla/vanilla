<?php

namespace Vanilla\Email;

use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\SendGridEmailJobInterface;
use Vanilla\Scheduler\SchedulerInterface;

class SendGridMailer implements MailerInterface
{
    private ?string $htmlContent = null;

    private ?string $textContent = null;

    private string $subject = "";

    private string $fromEmail;

    private ?string $fromName = null;

    private ?string $senderEmail = null;

    private array $to = [];

    private array $cc = [];

    private array $bcc = [];

    private array $allRecipients = [];

    private array $headers = [];

    private string $contentType = self::CONTENT_TYPE_TEXT;

    private ?int $activityID = null;

    /**
     * @inheritdoc
     */
    public function send(): bool
    {
        $job = new NormalJobDescriptor(SendGridEmailJobInterface::class);
        $job->setMessage([
            "subject" => $this->subject,
            "fromName" => $this->fromName,
            "from" => [
                "email" => $this->fromEmail,
                "name" => $this->fromName,
            ],
            "to" => $this->to,
            "cc" => $this->cc,
            "bcc" => $this->bcc,
            "headers" => $this->headers,
            "htmlContent" => $this->htmlContent,
            "textContent" => $this->textContent,
            "activityID" => $this->activityID,
        ]);
        $scheduler = \Gdn::getContainer()->get(SchedulerInterface::class);
        $scheduler->addJobDescriptor($job);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function addHeader(string $name, ?string $value = null): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @inheritdoc
     */
    public function addTo(string $recipientMail, ?string $recipientName = null): void
    {
        $this->to[] = ["email" => $recipientMail, "name" => $recipientName];
        $this->allRecipients[strtolower($recipientMail)] = true;
    }

    /**
     * @inheritdoc
     */
    public function addCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->cc[] = ["email" => $recipientMail, "name" => $recipientName];
        $this->allRecipients[strtolower($recipientMail)] = true;
    }

    /**
     * @inheritdoc
     */
    public function addBCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->bcc[] = ["email" => $recipientMail, "name" => $recipientName];
        $this->allRecipients[strtolower($recipientMail)] = true;
    }

    /**
     * @inheritdoc
     */
    public function setFrom(string $senderEmail, ?string $senderName = null): void
    {
        $this->fromEmail = $senderEmail;
        $this->fromName = $senderName;
    }

    /**
     * @inheritdoc
     */
    public function getFromAddress(): string
    {
        return $this->fromEmail;
    }

    /**
     * @inheritdoc
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @inheritdoc
     */
    public function getToAddresses(): array
    {
        return $this->to;
    }

    /**
     * @inheritdoc
     */
    public function getCcAddresses(): array
    {
        return $this->cc;
    }

    /**
     * @inheritdoc
     */
    public function getBccAddresses(): array
    {
        return $this->bcc;
    }

    /**
     * @inheritdoc
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @inheritdoc
     */
    public function getSender(): ?string
    {
        return $this->senderEmail;
    }

    /**
     * @inheritdoc
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @inheritdoc
     */
    public function setContentType(string $type): void
    {
        if (!in_array($type, [self::CONTENT_TYPE_TEXT, self::CONTENT_TYPE_HTML])) {
            throw new \InvalidArgumentException("Not a valid Content-Type: $type");
        }
        $this->contentType = $type;
    }

    /**
     * @inheritdoc
     */
    public function getCharSet(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function setSubject(string $subject, bool $encode = true): void
    {
        $this->subject = $subject;
    }

    /**
     * @inheritdoc
     */
    public function isSingleTo(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function clearRecipients(): void
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->allRecipients = [];
    }

    /**
     * @inheritdoc
     */
    public function clearContent(): void
    {
        $this->htmlContent = null;
        $this->textContent = null;
    }

    /**
     * @inheritdoc
     */
    public function setTextContent(string $content = ""): void
    {
        $this->textContent = $content;
    }

    /**
     * @inheritdoc
     */
    public function setHtmlContent(string $content = ""): void
    {
        $this->htmlContent = $content;
    }

    /**
     * @inheritdoc
     */
    public function setTextOnlyContent(string $content = ""): void
    {
        $this->setTextContent($content);
    }

    /**
     * @inheritdoc
     */
    public function getTextOnlyContent(): ?string
    {
        return $this->getTextContent();
    }

    /**
     * @inheritdoc
     */
    public function setSender(string $senderEmail): void
    {
        $this->senderEmail = $senderEmail;
    }

    /**
     * @inheritdoc
     */
    public function getAllRecipients(): array
    {
        return $this->allRecipients;
    }

    /**
     * @inheritdoc
     */
    public function setBodyContent(string $content = ""): void
    {
        $this->setHtmlContent($content);
    }

    /**
     * @inheritdoc
     */
    public function getBodyContent(): ?string
    {
        return $this->htmlContent;
    }

    /**
     * @inheritdoc
     */
    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    /**
     * @inheritdoc
     */
    public function setActivityID(int $activityID): void
    {
        $this->activityID = $activityID;
    }
}
