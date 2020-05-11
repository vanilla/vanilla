<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Contracts\RecordInterface;

/**
 * Generic record for a given type.
 */
class GenericRecord implements RecordInterface {

    /** @var string */
    private $recordType;

    /** @var int */
    private $recordID;

    /**
     * Constructor.
     *
     * @param string $recordType
     * @param int $recordID
     */
    public function __construct(string $recordType, int $recordID) {
        $this->recordType = $recordType;
        $this->recordID = $recordID;
    }

    /**
     * @inheritdoc
     */
    public function getRecordType(): string {
        return $this->recordType;
    }

    /**
     * @inheritdoc
     */
    public function getRecordID(): int {
        return $this->recordID;
    }
}
