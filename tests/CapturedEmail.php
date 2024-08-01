<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use VanillaTests\Fixtures\Html\TestHtmlDocument;

/**
 * A data class for capturing sent emails in tests.
 */
class CapturedEmail
{
    /**
     * @var string|null
     */
    public $subject;

    /**
     * @var \EmailTemplate
     */
    public $template;

    /**
     * @var string
     */
    private $body;

    /**
     * @var CapturedEmailAddress[]
     */
    private $to;

    /**
     * @var CapturedEmailAddress[]
     */
    private $cc;

    /**
     * @var CapturedEmailAddress[]
     */
    private $bcc;

    public string $format;

    /**
     * CapturedEmail constructor.
     */
    public function __construct()
    {
    }

    /**
     * Add a PHP Mailer style array of email addresses to one of our arrays.
     *
     * @param CapturedEmailAddress[] $arr
     * @param string[][] $emails
     */
    private function addEmailAddresses(&$arr, $emails): void
    {
        foreach ($emails as $email) {
            $arr[] = new CapturedEmailAddress((string) $email["email"], (string) $email["name"]);
        }
    }

    /**
     * Find an email.
     *
     * @param string|null $email
     * @param string|null $name
     * @return CapturedEmailAddress|null
     */
    public function findRecipient(?string $email, ?string $name = null): ?CapturedEmailAddress
    {
        $to = $this->findRecipientOn($this->to, $email, $name);
        if ($to) {
            return $to;
        }
        $to = $this->findRecipientOn($this->cc ?? [], $email, $name);
        if ($to) {
            return $to;
        }
        $to = $this->findRecipientOn($this->bcc ?? [], $email, $name);
        if ($to) {
            return $to;
        }
        return null;
    }

    /**
     * Find an email on a specific recipient array.
     *
     * @param array $arr The array to search.
     * @param string|null $email The email to search for or null for all email addresses.
     * @param string|null $name The name to search for  or null for all names.
     * @return CapturedEmailAddress|null
     */
    public function findRecipientOn(array $arr, ?string $email, ?string $name): ?CapturedEmailAddress
    {
        /** @var CapturedEmailAddress $to */
        foreach ($arr as $to) {
            if ($email !== null && $to->email !== $email) {
                continue;
            }
            if ($name !== null && $to->name !== $name) {
                continue;
            }
            return $to;
        }
        return null;
    }

    /**
     * Get a test HTML document of the email contents.
     *
     * @return TestHtmlDocument
     */
    public function getHtmlDocument(): TestHtmlDocument
    {
        return new TestHtmlDocument($this->body, false);
    }

    /**
     * Create a captured email from a `Gdn_Email` object.
     *
     * @param \Gdn_Email $email
     * @return CapturedEmail $email
     */
    public static function fromEmail(\Gdn_Email $email): self
    {
        $r = new self();
        $r->subject = $email->getMailer()->getSubject();
        $r->body = $email->getMailer()->getBodyContent();
        $r->format = $email->getFormat();
        $r->addEmailAddresses($r->to, $email->getMailer()->getToAddresses());
        $r->addEmailAddresses($r->cc, $email->getMailer()->getCcAddresses());
        $r->addEmailAddresses($r->bcc, $email->getMailer()->getBccAddresses());
        $r->template = $email->getEmailTemplate();

        return $r;
    }
}
