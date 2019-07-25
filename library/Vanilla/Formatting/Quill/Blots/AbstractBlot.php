<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots;

use Vanilla\Formatting\Quill\BlotGroup;
use Vanilla\Formatting\Quill\Parser;

/**
 * All blots extend AbstractBlot. Even formats. Blots map lightly to quill blots.
 *
 * This is pretty bare-bones so you likely want to extend TextBlot or AbstractFormat instead.
 * See https://github.com/quilljs/parchment#blots for an explanation of the JS implementation of quill (parchment) blots.
 */
abstract class AbstractBlot {

    /** @var string */
    protected $parseMode;

    /** @var string */
    protected $content = "";

    /**
     * @var array The primary operation of the blot.
     * This Blot OWNS that operation. This always contains content for the blot.
     */
    protected $currentOperation = [];

    /**
     * @var array The previous operation. This should never contain content for the blot.
     *
     * Primary uses:
     * - Formats use the previous blot for optimizing opening tags.
     *
     * @see AbstractFormat::shouldRenderOpeningTag()
     */
    protected $previousOperation = [];

    /**
     * @var array The next operation from the currentOperation. This may contain additional content in certain blots.
     *
     * Primary uses:
     * - Block level blots like Headings/Code/Line blots store their attributes in the next blot (WHY?!).
     * - LineBlots keep all of their additional newlines in the next blot as well.
     * - Formats use the next blot for optimizing closing tags.
     *
     * @see AbstractFormat::shouldRenderClosingTag()
     */
    protected $nextOperation = [];

    /**
     * Determine if the operation matches this Blot type.
     *
     * @param array $operation An operation to check.
     *
     * @return bool
     */
    abstract public static function matches(array $operation): bool;

    /**
     * Render the blot into an HTML string.
     *
     * @return string
     */
    abstract public function render(): string;

    /**
     * Render this blot like a quote format.
     *
     * @return string
     */
    public function renderQuote(): string {
        return $this->render();
    }

    /**
     * Determine whether or not this blot uses both current and next operation.
     *
     * If the next operation matched, but not the current one, this is usually the case.
     *
     * @return bool
     */
    public function hasConsumedNextOp(): bool {
        return $this::matches([$this->nextOperation]) && !$this::matches([$this->currentOperation]);
    }

    /**
     * Determine if the blot should be 100% alone in a BlotGroup.
     *
     * @return bool
     */
    public function isOwnGroup(): bool {
        return false;
    }

    /**
     * Get the HTML to represent the opening tag of the Group this is contained in.
     *
     * @return string
     */
    public function getGroupOpeningTag(): string {
        return "<p>";
    }

    /**
     * Get the HTML to represent the closing tag of the Group this is container in.
     *
     * @return string
     */
    public function getGroupClosingTag(): string {
        return "</p>";
    }

    /**
     * Determine whether or not this Blot should clear the current Group.
     *
     * @param BlotGroup $group The current group being built.
     *
     * @return bool
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return $this->isOwnGroup();
    }

    /**
     * Get the content of the blot.
     *
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * Get the nesting depth of the blot.
     *
     * @return int
     */
    public function getNestingDepth(): int {
        return 0;
    }

    /**
     * Create a blot.
     *
     * @param array $currentOperation The current operation.
     * @param array $previousOperation The next operation.
     * @param array $nextOperation The previous operation.
     * @param string $parseMode The parse mode to create the blot with.
     */
    public function __construct(
        array $currentOperation,
        array $previousOperation = [],
        array $nextOperation = [],
        string $parseMode = Parser::PARSE_MODE_NORMAL
    ) {
        $this->previousOperation = $previousOperation;
        $this->currentOperation = $currentOperation;
        $this->nextOperation = $nextOperation;
        $this->parseMode = $parseMode;
    }
}
