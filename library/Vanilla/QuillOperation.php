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

    const NEWLINE_TYPE_START = "start";
    const NEWLINE_TYPE_END = "end";
    const NEWLINE_TYPE_ONLY = "only";
    const NEWLINE_TYPE_NONE = "none";

    private $allowedInsertTypes = [
        self::INSERT_TYPE_STRING,
        self::INSERT_TYPE_IMAGE,
    ];

    /** @var string The content of the operation  */
    public $content = "";

    /** @var string The type of insert. */
    public $insertType = self::INSERT_TYPE_STRING;

    /** @var bool The block type of the insert. */
    public $inline = false;

    /** @var bool Does the text have strike-through. */
    public $strike = false;

    /** @var bool Is the text bold. */
    public $bold = false;

    /** @var bool Is the text italic. */
    public $italic = false;

    /** @var int What level of indentation does the item have. */
    public $indent = 0;

    /** @var bool */
    public $list = false;

    /** @var string|null Is this item a link? If so, what is the link. */
    public $link = null;

    /** @var string Does this item start of end with a newline. */
    public $newline = self::NEWLINE_TYPE_NONE;

    /** @var array All attributes directly from the source. These shouldn't be used directly in rendering. */
    public $attributes = [];

    private $newlineOnlyRegexp = "/^\\n$/";
    private $newlineStartRegexp = "/^\\n/";
    private $newlineEndRegexp = "/\\n$/";

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
            $this->attributes = $attributes;

            // Boolean Values
            $booleanAttributes = [
                "strike",
                "bold",
                "italic",
                "list"
            ];

            foreach ($booleanAttributes as $attr) {
                if (val($attr, $attributes)) {
                    $this->{$attr} = val($attr, $attributes);
                    $this->inline = true;
                }
            }

            if (val("link", $attributes)) {
                $this->link = val("link", $attributes);
                $this->inline = true;
            }

            // Numbered Values
            $numberedAttributes = [
                "indent",
            ];

            foreach($numberedAttributes as $attr) {
                if (is_int(val($attr, $attributes))) {
                    $this->{$attr} = $attributes[$attr];
                }
            }

            if (val("header", $attributes) > 0) {
                $this->inline = false;
            }
        }

        if (preg_match($this->newlineOnlyRegexp, $this->content)) {
            $this->newline = self::NEWLINE_TYPE_ONLY;
            $this->stripStartingNewLine();
        } elseif (preg_match($this->newlineStartRegexp, $this->content)) {
            $this->newline = self::NEWLINE_TYPE_START;
            $this->stripStartingNewLine();
        } elseif (preg_match($this->newlineEndRegexp, $this->content)) {
            $this->newline = self::NEWLINE_TYPE_END;
            $this->stripEndingNewLine();
        } else {
            $this->newline = self::NEWLINE_TYPE_NONE;
        }

        // If there is only a newline left, replace it with with a break tag.
        if (preg_match($this->newlineOnlyRegexp, $this->content)) {
            $this->content = preg_replace($this->newlineOnlyRegexp, "<br>", $this->content);

        }
    }

    private function stripStartingNewLine() {
        $this->content = preg_replace($this->newlineStartRegexp, "", $this->content);
    }

    private function stripEndingNewLine() {
        $this->content = preg_replace($this->newlineEndRegexp, "", $this->content);
    }
}
