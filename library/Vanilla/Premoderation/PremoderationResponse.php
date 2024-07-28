<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

/**
 * Result from a premoderation hook.
 */
class PremoderationResponse
{
    const VALID = "valid";
    const SPAM = "spam";
    const SUPER_SPAM = "super-spam";
    const APPROVAL_REQUIRED = "approval-required";

    private string $noteHtml = "";

    /**
     * @param string $responseType
     * @param ?int $moderatorUserID
     */
    public function __construct(private string $responseType, private ?int $moderatorUserID)
    {
    }

    public function getNoteHtml(): string
    {
        return $this->noteHtml;
    }

    public function setNoteHtml(string $noteHtml): void
    {
        $this->noteHtml = $noteHtml;
    }

    /**
     * @return PremoderationResponse
     */
    public static function valid(): PremoderationResponse
    {
        return new PremoderationResponse(self::VALID, null);
    }

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return $this->responseType;
    }

    /**
     * @return string
     */
    public function isApprovalRequired(): string
    {
        return $this->responseType === self::APPROVAL_REQUIRED;
    }

    /**
     * @return bool
     */
    public function isSpam(): bool
    {
        return in_array($this->responseType, [self::SPAM, self::SUPER_SPAM]);
    }

    /**
     * Super spam is something that was flagged as so bad that we don't even consider it for moderation.
     *
     * @return bool
     */
    public function isSuperSpam(): bool
    {
        return $this->responseType === self::SUPER_SPAM;
    }

    /**
     * @return ?int
     */
    public function getModeratorUserID(): ?int
    {
        return $this->moderatorUserID;
    }
}
