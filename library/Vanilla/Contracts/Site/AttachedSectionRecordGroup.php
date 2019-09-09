<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Class representing the records attached to a site section.
 */
class AttachedSectionRecordGroup implements \Countable, \JsonSerializable {

    /** @var array */
    private $attachedRecordIDs;

    /** @var string */
    private $recordType;

    /** @var string */
    private $recordTypeDisplayName;

    /**
     * Constructor.
     *
     * @param array $attachedRecordIDs
     * @param string $recordType
     * @param string $recordTypeDisplayName
     */
    public function __construct(array $attachedRecordIDs, string $recordType, string $recordTypeDisplayName) {
        $this->attachedRecordIDs = $attachedRecordIDs;
        $this->recordType = $recordType;
        $this->recordTypeDisplayName = $recordTypeDisplayName;
    }

    /**
     * @inheritdoc
     */
    public function count(): int {
        return count($this->attachedRecordIDs);
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array {
        return [
            'attachedRecordIDs' => $this->attachedRecordIDs,
            'recordType' => $this->recordType,
            'recordTypeDisplayName' => $this->recordTypeDisplayName,
        ];
    }

    /**
     * @return array
     */
    public function getAttachedRecordIDs(): array {
        return $this->attachedRecordIDs;
    }

    /**
     * @return string
     */
    public function getRecordType(): string {
        return $this->recordType;
    }

    /**
     * @return string
     */
    public function getRecordTypeDisplayName(): string {
        return $this->recordTypeDisplayName;
    }
}
