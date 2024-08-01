<?php

namespace Vanilla\Email;

interface MailerInterface
{
    const CONTENT_TYPE_TEXT = "text/plain";
    const CONTENT_TYPE_HTML = "text/html";

    /**
     * Returns the subject.
     *
     * @return string
     */
    public function getSubject(): string;

    /**
     * Sets the subject.
     *
     * @param string $subject
     * @param bool $encode
     * @return void
     */
    public function setSubject(string $subject, bool $encode = true): void;

    /**
     * Add a header name-value pair.
     *
     * @param string $name
     * @param string|null $value
     * @return void
     */
    public function addHeader(string $name, ?string $value = null): void;

    /**
     * Adds a recipient to the "To" field.
     *
     * @param string $recipientMail
     * @param string|null $recipientName
     * @return void
     */
    public function addTo(string $recipientMail, ?string $recipientName = null): void;

    /**
     * Adds a recipient to the "Cc" field.
     *
     * @param string $recipientMail
     * @param string|null $recipientName
     * @return void
     */
    public function addCC(string $recipientMail, ?string $recipientName = null): void;

    /**
     * Adds a recipient to the "Bcc" field.
     *
     * @param string $recipientMail
     * @param string|null $recipientName
     * @return void
     */
    public function addBCC(string $recipientMail, ?string $recipientName = null): void;

    /**
     * Returns an array of recipients assigned to the "To" field.
     *
     * @return array
     */
    public function getToAddresses(): array;

    /**
     * Returns an array of recipients assigned to the "Cc" field.
     *
     * @return array
     */
    public function getCcAddresses(): array;

    /**
     * Returns an array of recipients assigned to the "Bcc" field.
     *
     * @return array
     */
    public function getBccAddresses(): array;

    /**
     * Returns an array mapping of all recipients indexed by email address.
     *
     * @return array
     */
    public function getAllRecipients(): array;

    /**
     * Clears all recipients.
     *
     * @return void
     */
    public function clearRecipients(): void;

    /**
     * Sets the Name and Email for the "From" field.
     *
     * @param string $senderEmail
     * @param string|null $senderName
     * @return void
     */
    public function setFrom(string $senderEmail, ?string $senderName = null): void;

    /**
     * Returns the "From" address.
     *
     * @return string
     */
    public function getFromAddress(): string;

    /**
     * Returns the "From" name.
     *
     * @return string
     */
    public function getFromName(): string;

    /**
     * Returns the "Sender".
     *
     * @return string|null
     */
    public function getSender(): ?string;

    /**
     * Sets the "Sender".
     *
     * @param string $senderEmail
     * @return void
     */
    public function setSender(string $senderEmail): void;

    /**
     * Returns the content-type of this email.
     *
     * @return string
     */
    public function getContentType(): string;

    /**
     * Sets the content-type of this email.
     *
     * @param string $type
     * @return void
     */
    public function setContentType(string $type): void;

    /**
     * Returns the charset for this email.
     *
     * @return string|null
     */
    public function getCharSet(): ?string;

    /**
     * Whether this mailer supports sending emails for each "To" in a separate email.
     *
     * @return bool
     */
    public function isSingleTo(): bool;

    /**
     * Return the text content for this email.
     *
     * @return string|null
     */
    public function getTextContent(): ?string;

    /**
     * Set the text content for this email.
     *
     * @param string $content
     * @return void
     */
    public function setTextContent(string $content = ""): void;

    /**
     * Set the html content for this email.
     *
     * @param string $content
     * @return void
     */
    public function setHtmlContent(string $content = ""): void;

    /**
     * Set the text-only content for this email.
     *
     * @param string $content
     * @return void
     */
    public function setTextOnlyContent(string $content = ""): void;

    /**
     * Return the text-only content for this email.
     *
     * @return string|null
     */
    public function getTextOnlyContent(): ?string;

    /**
     * Return the "Body" content for this email.
     * The "Body" content could be the HTML or text content depending on the mailer.
     *
     * @return string|null
     */
    public function getBodyContent(): ?string;

    /**
     * Set the "Body" content for this email.
     *
     * @param string $content
     * @return void
     */
    public function setBodyContent(string $content = ""): void;

    /**
     * Clear the HTML and plain text content.
     *
     * @return void
     */
    public function clearContent(): void;

    /**
     * Send this email.
     *
     * @return bool
     * @throws \Exception
     */
    public function send(): bool;

    /**
     * Set an activity ID if this email is associated with an `Activity` record.
     * @param int $activityID
     * @return void
     */
    public function setActivityID(int $activityID): void;
}
