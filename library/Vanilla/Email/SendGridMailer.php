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
     * @inheritDoc
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
     * @inheritDoc
     */
    public function addHeader(string $name, ?string $value = null): void
    {
        $this->headers[$name] = $value;
    }

    /**
     * @inheritDoc
     */
    public function addTo(string $recipientMail, ?string $recipientName = null): void
    {
        $this->to[] = ["email" => $recipientMail, "name" => $recipientName];
        $this->allRecipients[strtolower($recipientMail)] = true;
    }

    /**
     * @inheritDoc
     */
    public function addCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->cc[] = ["email" => $recipientMail, "name" => $recipientName];
        $this->allRecipients[strtolower($recipientMail)] = true;
    }

    /**
     * @inheritDoc
     */
    public function addBCC(string $recipientMail, ?string $recipientName = null): void
    {
        $this->bcc[] = ["email" => $recipientMail, "name" => $recipientName];
        $this->allRecipients[strtolower($recipientMail)] = true;
    }

    /**
     * @inheritDoc
     */
    public function setFrom(string $senderEmail, ?string $senderName = null): void
    {
        $this->fromEmail = $senderEmail;
        $this->fromName = $senderName;
    }

    /**
     * @inheritDoc
     */
    public function getFromAddress(): string
    {
        return $this->fromEmail;
    }

    /**
     * @inheritDoc
     */
    public function getFromName(): string
    {
        return $this->fromName;
    }

    /**
     * @inheritDoc
     */
    public function getToAddresses(): array
    {
        return $this->to;
    }

    /**
     * @inheritDoc
     */
    public function getCcAddresses(): array
    {
        return $this->cc;
    }

    /**
     * @inheritDoc
     */
    public function getBccAddresses(): array
    {
        return $this->bcc;
    }

    /**
     * @inheritDoc
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @inheritDoc
     */
    public function getSender(): ?string
    {
        return $this->senderEmail;
    }

    /**
     * @inheritDoc
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @inheritDoc
     */
    public function setContentType(string $type): void
    {
        if (!in_array($type, [self::CONTENT_TYPE_TEXT, self::CONTENT_TYPE_HTML])) {
            throw new \InvalidArgumentException("Not a valid Content-Type: $type");
        }
        $this->contentType = $type;
    }

    /**
     * @inheritDoc
     */
    public function getCharSet(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function setSubject(string $subject, bool $encode = true): void
    {
        $this->subject = $subject;
    }

    /**
     * @inheritDoc
     */
    public function isSingleTo(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function clearRecipients(): void
    {
        $this->to = [];
        $this->cc = [];
        $this->bcc = [];
        $this->allRecipients = [];
    }

    /**
     * @inheritDoc
     */
    public function clearContent(): void
    {
        $this->htmlContent = null;
        $this->textContent = null;
    }

    /**
     * @inheritDoc
     */
    public function setTextContent(string $content = ""): void
    {
        $this->textContent = $content;
    }

    /**
     * @inheritDoc
     */
    public function setHtmlContent(string $content = ""): void
    {
        $this->htmlContent = $content;
    }

    /**
     * @inheritDoc
     */
    public function setTextOnlyContent(string $content = ""): void
    {
        $this->setTextContent($content);
    }

    /**
     * @inheritDoc
     */
    public function getTextOnlyContent(): ?string
    {
        return $this->getTextContent();
    }

    /**
     * @inheritDoc
     */
    public function setSender(string $senderEmail): void
    {
        $this->senderEmail = $senderEmail;
    }

    /**
     * @inheritDoc
     */
    public function getAllRecipients(): array
    {
        return $this->allRecipients;
    }

    /**
     * @inheritDoc
     */
    public function setBodyContent(string $content = ""): void
    {
        $this->setHtmlContent($content);
    }

    /**
     * @inheritDoc
     */
    public function getBodyContent(): ?string
    {
        return $this->htmlContent;
    }

    /**
     * @inheritDoc
     */
    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    /**
     * @inheritDoc
     */
    public function setActivityID(int $activityID): void
    {
        $this->activityID = $activityID;
    }
}
