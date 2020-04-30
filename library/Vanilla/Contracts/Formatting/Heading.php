<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Formatting;

/**
 * Plain old object to represent a heading.
 */
class Heading implements \JsonSerializable {

    /** @var string The text content of the heading. */
    public $text;

    /** @var int The level of the heading. Ex. 2 -> <h2> 3 -> <h3> */
    public $level;

    /** @var string A deterministic unique key to represent the heading. */
    public $ref;

    /**
     * Heading constructor.
     *
     * @param string $text
     * @param int $level
     * @param string $ref
     */
    public function __construct(string $text, int $level, string $ref) {
        $this->text = $text;
        $this->level = $level;
        $this->ref = $ref;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return [
            'text' => $this->text,
            'level' => $this->level,
            'ref' => $this->ref,
        ];
    }
}
