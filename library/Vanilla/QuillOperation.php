<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
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
    const NEWLINE_TYPE_ATTRIBUTOR = "attibutor";

    // List types
    const LIST_TYPE_BULLET = "bullet";
    const LIST_TYPE_ORDERED = "ordered";
    const LIST_TYPE_NONE = "none";

    /** @var string The content of the operation  */
    private $content = "";

    /** @var string The type of insert. */
    private $insertType = self::INSERT_TYPE_STRING;

    /** @var string */
    private $listType = self::LIST_TYPE_NONE;

    /** @var string Does this item start of end with a newline. */
    private $newlineType = self::NEWLINE_TYPE_NONE;

    /** @var int */
    private $indent = 0;

    /** @var array All attributes directly from the source. These shouldn't be used directly in rendering. */
    private $attributes = [];

    private $newlineStartRegexp = "/^\\n/";
    private $newlineEndRegexp = "/\\n$/";

    public function __construct($operationArray) {
        $insert = val("insert", $operationArray);
        if (is_string($insert)) {
            $this->insertType = self::INSERT_TYPE_STRING;
            $this->content = $insert;
        } elseif (is_array($insert) && $insert["image"]) {
            $this->insertType = self::INSERT_TYPE_IMAGE;
            $this->content = $insert;
        }

        $this->attributes = val("attributes", $operationArray, []);
        $this->listType = $this->getAttribute("list", self::LIST_TYPE_NONE);
        $this->indent = $this->getAttribute("indent", 0);

        $isList = $this->listType !== self::LIST_TYPE_NONE;
        $isCodeBlock = $this->getAttribute("code-block");
        $isQuote = $this->getAttribute("blockquote");
        $isHeader = $this->getAttribute("header");

        // Parse new lines.
        if ($this->content === "\n") {
            $this->newline = self::NEWLINE_TYPE_ONLY;
            if ($isList || $isCodeBlock || $isQuote || $isHeader) {
                $this->stripStartingNewLine();

                $this->newline = self::NEWLINE_TYPE_ATTRIBUTOR;
            }
        } elseif (preg_match($this->newlineStartRegexp, $this->content)) {
            $this->newline = self::NEWLINE_TYPE_START;
            $this->stripStartingNewLine();
        } elseif (preg_match($this->newlineEndRegexp, $this->content)) {
            $this->newline = self::NEWLINE_TYPE_END;
            $this->stripEndingNewLine();
        } else {
            $this->newline = self::NEWLINE_TYPE_NONE;
        }
    }

    /**
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content) {
        $this->content = $content;
    }

    /**
     * @return string
     */
    public function getInsertType(): string {
        return $this->insertType;
    }

    /**
     * @return string
     */
    public function getListType(): string {
        return $this->listType;
    }

    /**
     * @param string $listType
     */
    public function setListType(string $listType) {
        $this->listType = $listType;
    }

    /**
     * @return string
     */
    public function getNewlineType(): string {
        return $this->newline;
    }

    /**
     * @param string $newlineType
     */
    public function setNewlineType(string $newlineType) {
        $this->newlineType = $newlineType;
    }

    /**
     * @return int
     */
    public function getIndent(): int {
        return $this->indent;
    }

    /**
     * @param int $indent
     */
    public function setIndent(int $indent) {
        $this->indent = $indent;
    }

    /**
     * Get an attribute out of the operation by string name.
     *
     * @param string $name - The attribute to look up.
     * @param bool $default - The default value to return if not found.
     *
     * @return mixed
     */
    public function getAttribute(string $name, $default = false) {
        return val($name, $this->attributes, $default);
    }

    /**
     * Strip off a starting newline character from the objects content.
     */
    private function stripStartingNewLine() {
        $this->content = ltrim($this->content, "\n");
    }

    /**
     * Strip off a trailing newline character from the objects content.
     */
    private function stripEndingNewLine() {
        $this->content = rtrim($this->content, "\n");
    }
}
