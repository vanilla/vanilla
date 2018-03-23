<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

abstract class AbstractLineBlot extends AbstractBlockBlot {

    private $needsOpeningTag = true;
    private $needsClosingTag = true;
    private $needsTrailingOpenTag = false;
    private $needsStartingClosingTag = false;

    /**
     * Get the main part of the line name.
     *
     * @return string
     */
    abstract protected static function getLineType(): string;

    /**
     * @inheritDoc
     */
    protected static function getAttributeKey(): string {
        return static::getLineType() . "-line";
    }

    /**
     * @inheritDoc
     */
    public static function isOwnGroup(): bool {
        return false;
    }

    private function renderContent(): string {
        $class = static::getAttributeKey();
        $result = "";
        if ($this->needsOpeningTag) {
            $result .= "<p class=\"$class\">";
        }

        $result .= parent::render();
        if ($this-> needsClosingTag) {
            $result .= "</p>";
        }

        return $result;
    }

    public function renderNewLines(): string {
        $class = static::getAttributeKey();
        $result = "";
        if ($this->nextOperation) {
            $extraNewLines = \substr_count($this->nextOperation["insert"], "\n") - 1;
            for ($i = 0; $i < $extraNewLines; $i++) {
                $result .= "<p class=\"$class\"><br></p>";
            }
        }

        return $result;
    }

    public function renderLineStart(): string {
        $class = static::getAttributeKey();
        return "<p class=\"$class\">";
    }

    public function renderLineEnd(): string {
        return "</p>";
    }

    /**
     * @inheritDoc
     */
//    public function render(): string {
////        return $this->content;
//    }

    /**
     * @param bool $needsOpeningTag
     */
    public function setNeedsOpeningTag(bool $needsOpeningTag) {
        $this->needsOpeningTag = $needsOpeningTag;
    }

    /**
     * @param bool $needsClosingTag
     */
    public function setNeedsClosingTag(bool $needsClosingTag) {
        $this->needsClosingTag = $needsClosingTag;
    }

    /**
     * @param bool $needsTrailingOpenTag
     */
    public function setNeedsTrailingOpenTag(bool $needsTrailingOpenTag) {
        $this->needsTrailingOpenTag = $needsTrailingOpenTag;
    }

    /**
     * @param bool $needsStartingClosingTag
     */
    public function setNeedsStartingClosingTag(bool $needsStartingClosingTag) {
        $this->needsStartingClosingTag = $needsStartingClosingTag;
    }
}
