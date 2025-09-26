<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

/**
 * Class representing a fragment being applied to some location.
 */
final class FragmentView implements \JsonSerializable
{
    public function __construct(
        public string $fragmentUUID,
        public string $recordType,
        public string $recordID,
        public string $recordName,
        public string $recordUrl
    ) {
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return [
            "recordType" => $this->recordType,
            "recordID" => $this->recordID,
            "recordName" => $this->recordName,
            "recordUrl" => $this->recordUrl,
        ];
    }
}
