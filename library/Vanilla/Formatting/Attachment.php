<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Plain old object to represent and attachment.
 */
class Attachment implements \JsonSerializable {

    /** @var string The file name of the attachment. */
    public $name;

    /** @var \DateTimeInterface The date the attachment was uploaded. */
    public $dateInserted;

    /** @var int The size of the attachment in bytes. */
    public $size;

    /** @var string The string type of the attachment. */
    public $type;

    /**
     * Attachment Constructor.
     *
     * @param string $name
     * @param \DateTimeInterface $dateInserted
     * @param int $size
     * @param string $type
     */
    public function __construct(string $name, \DateTimeInterface $dateInserted, int $size, string $type) {
        $this->name = $name;
        $this->dateInserted = $dateInserted;
        $this->size = $size;
        $this->type = $type;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return [
            'name' => $this->name,
            'dateInserted' => $this->dateInserted,
            'size' => $this->size,
            'type' => $this->type,
        ];
    }
}
