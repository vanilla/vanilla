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

    private $allowedListTypes = [
        self::LIST_TYPE_NONE,
        self::LIST_TYPE_BULLET,
        self::LIST_TYPE_ORDERED,
    ];

    /** @var string The content of the operation  */
    public $content = "";

    /** @var string The type of insert. */
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
            // List values
            $list = val("list", $attributes);

            if ($list && in_array($list, $this->allowedListTypes)) {
                $this->list = $list;
            }

            // Boolean Values
            $booleanAttributes = [
                "codeBlock" => "code-block",
                "strike",
                "bold",
                "italic",
            ];

            foreach ($booleanAttributes as $key => $value) {
                if (in_array($value, $booleanAttributes)) {
                    $classKey = is_int($key) ? $value : $key;
                    $this->{$value} = val($classKey, $attributes);
                }
            }

            // Numbered Values
            $numberedAttributes = [
                "indent",
                "header",
            ];

            foreach($numberedAttributes as $attr) {
                if (is_int(val($attr, $attributes))) {
                    $this->{$attr} = $attributes[$attr];
                }
            }
        }

        $this->validate();
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

        if($this->header > 0)

        return true;
    }
}
