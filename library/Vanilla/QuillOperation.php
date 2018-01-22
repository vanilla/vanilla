<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;

/**
 * A class used to parse a Quill Delta into workable, strictly whitelisted PHP.
 */
class QuillOperation {

    const INSERT_TYPE_STRING = "string";
    const INSERT_TYPE_IMAGE = "image";
    const LIST_TYPE_BULLET = "bullet";
    const LIST_TYPE_ORDERED = "ordered";
    const LIST_TYPE_NONE = "none";

    private $allowedInsertTypes = [
        self::INSERT_TYPE_STRING,
        self::INSERT_TYPE_IMAGE,
    ];

    /**
     * The type of insert.
     */
    public $insertType = self::INSERT_TYPE_STRING;

    /** @var bool Is the text formatted as a code block. */
    public $codeBlock = false;

    /** @var bool Does the text have strike-through. */
    public $strike = false;

    /** @var bool Is the text bold. */
    public $bold = false;

    /** @var bool Is the text italic. */
    public $italic = false;

    /** @var string What type of list is the item. */
    public $list = self::LIST_TYPE_NONE;

    /** @var int What level of indentation does the item have. */
    public $indent = 0;

    /** @var int What level of heading is this item. */
    public $header = 0;

    /** @var string|null Is this item a link? If so, what is the link. */
    public $link = null;

    /** @var string|null If this item is an image provides the image url. */
    public $image = null;

    public function __construct(array $operationArray) {

    }
}
