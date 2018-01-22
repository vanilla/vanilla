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
    const BLOCK_TYPE_PARAGRAPH = "paragraph";
    const BLOCK_TYPE_CODE = "code";
    const BLOCK_TYPE_BLOCKQUOTE = "blockquote";
    const BLOCK_TYPE_HEADER = "header";
    const BLOCK_TYPE_EMBED = "embed";
    const BLOCK_TYPE_LIST = "list";

    private $allowedInsertTypes = [
        self::INSERT_TYPE_STRING,
        self::INSERT_TYPE_IMAGE,
    ];

    private $allowedListTypes = [
        self::LIST_TYPE_NONE,
        self::LIST_TYPE_BULLET,
        self::LIST_TYPE_ORDERED,
    ];

    private $allowedBlockTypes = [
        self::BLOCK_TYPE_BLOCKQUOTE,
        self::BLOCK_TYPE_CODE,
        self::BLOCK_TYPE_EMBED,
        self::BLOCK_TYPE_HEADER,
        self::BLOCK_TYPE_PARAGRAPH,
        self::BLOCK_TYPE_LIST,
    ];

    /** @var string The content of the operation  */
    public $content = "";

    /** @var string The type of insert. */
    public $insertType = self::INSERT_TYPE_STRING;

    /** @var string The block type of the insert. */
    public $blockType = self::BLOCK_TYPE_PARAGRAPH;

    /** @var bool Does the text have strike-through. */
    public $strike = false;

    /** @var bool Is the text bold. */
    public $bold = false;

    /** @var bool Is the text italic. */
    public $italic = false;

    /** @var string What type of list is the item. */
    public $listType = self::LIST_TYPE_NONE;

    /** @var int What level of indentation does the item have. */
    public $indent = 0;

    /** @var int What level of heading is this item. */
    public $headerLevel = 0;

    /** @var string|null Is this item a link? If so, what is the link. */
    public $link = null;

    public function __construct($operationArray) {
        $insert = $operationArray["insert"];
        if (is_string($insert)) {
            $this->insertType = self::INSERT_TYPE_STRING;
            $this->content = $insert;
        } elseif (is_array($insert) && $insert["image"]) {
            $this->insertType = self::INSERT_TYPE_IMAGE;
            $this->content = $insert;
        }

        $attributes = val("attributes", $operationArray);

        if ($attributes) {
            // Get Block Type
            if (val("code-block", $attributes)) {
                $this->blockType = self::BLOCK_TYPE_CODE;
            } elseif (val("list", $attributes)) {
                $this->blockType = self::BLOCK_TYPE_LIST;
                if (val("indent", $attributes)) {
                    $this->indent = val("indent", $attributes);
                }
            } elseif (val("header", $attributes)) {
                $this->blockType = self::BLOCK_TYPE_HEADER;
                $this->headerLevel = val("header", $attributes);
            } elseif (val("blockquote", $attributes)) {
                $this->blockType = self::BLOCK_TYPE_BLOCKQUOTE;
            } else {
                $this->blockType = self::BLOCK_TYPE_PARAGRAPH;
            }

            // List values
            $list = val("list", $attributes);

            if ($list && in_array($list, $this->allowedListTypes)) {
                $this->listType = $list;
            }

            // Boolean Values
            $booleanAttributes = [
                "strike",
                "bold",
                "italic",
            ];

            foreach ($booleanAttributes as $attr) {
                if (val($attr, $attributes)) {
                    $this->{$attr} = val($attr, $attributes);
                }
            }

            // Numbered Values
            $numberedAttributes = [
                "indent",
                "headerLevel",
            ];

            foreach($numberedAttributes as $attr) {
                if (is_int(val($attr, $attributes))) {
                    $this->{$attr} = $attributes[$attr];
                }
            }
        }
    }

    /**
     * Validate inserted values;
     *
     * @returns bool
     */
    public function validate(): bool {
        if (!in_array($this->insertType, $this->allowedInsertTypes)) {
            return false;
        }

        if (!in_array($this->list, $this->allowedListTypes)) {
            return false;
        }

        // Only one block level element may be active at a time.

        $onlyOneCanBeTrue = [
            $this->header > 0,
            $this->code,
            $this->blockquote,
        ];

        if (count(array_filter($onlyOneCanBeTrue)) > 1) {
            return false;
        }

        return true;
    }
}
