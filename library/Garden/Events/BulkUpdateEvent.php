<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

/**
 * An event indicating bulk update of some resource.
 */
class BulkUpdateEvent implements \JsonSerializable {

    /**
     * @var string The record type being updated.
     */
    private $recordType;

    /**
     * @var array An array of $field -> $value | $value[] to query by.
     */
    private $where;

    /**
     * @var array An array of $field -> $value to save.
     */
    private $fieldUpdates;

    /**
     * Constructor.
     *
     * @param string $recordType
     * @param array $where
     * @param array $fieldUpdates
     */
    public function __construct(string $recordType, array $where, array $fieldUpdates) {
        $this->recordType = $recordType;
        $this->where = $where;
        $this->fieldUpdates = $fieldUpdates;
    }

    /**
     * @return string
     */
    public function getRecordType(): string {
        return $this->recordType;
    }

    /**
     * @return array
     */
    public function getWhere(): array {
        return $this->where;
    }

    /**
     * @return array
     */
    public function getFieldUpdates(): array {
        return $this->fieldUpdates;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return [
            'type' => $this->getRecordType(),
            'where' => $this->getWhere(),
            'fieldUpdates' => $this->getFieldUpdates(),
        ];
    }

    /**
     * Convert to string.
     */
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }
}
