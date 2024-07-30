<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

/**
 * Collection of all premoderation responses.
 */
class PremoderationResult
{
    /**
     * @param PremoderationResponse[] $responses
     */
    public function __construct(private array $responses)
    {
    }

    /**
     * @return bool
     */
    public function isSpam(): bool
    {
        $result = false;
        foreach ($this->responses as $response) {
            if ($response->isSpam()) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * Super spam is something that was flagged as so bad that we don't even consider it for moderation.
     *
     * @return bool
     */
    public function isSuperSpam(): bool
    {
        $result = false;
        foreach ($this->responses as $response) {
            if ($response->isSuperSpam()) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    public function isApprovalRequired(): bool
    {
        $result = false;
        foreach ($this->responses as $response) {
            if ($response->isApprovalRequired()) {
                $result = true;
                break;
            }
        }
        return $result;
    }

    /**
     * @return PremoderationResponse[]
     */
    public function getResponses(): array
    {
        return $this->responses;
    }
}
