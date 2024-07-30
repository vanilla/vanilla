<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

class PremoderationItem
{
    public function __construct(
        // You may wonder, why are username and email here instead of just using the ones in session?
        // Well this is used on registration requests sometimes and the session is not yet set.
        public int $userID,
        public string $userName,
        public string $userEmail,
        public ?string $recordName,
        public string $recordBody,
        public string $recordFormat,
        public bool $isEdit,
        public string $placeRecordType,
        public int $placeRecordID,
        public string $recordType,
        public ?int $recordID,
        public array $rawRow
    ) {
    }
}
