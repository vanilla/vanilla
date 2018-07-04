<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\BlotGroup;
use Vanilla\Quill\Formats\AbstractFormat;

/**
 * All blots extend AbstractBlot. Even formats. Blots map lightly to quill blots.
 *
 * This is pretty bare-bones so you likely want to extend TextBlot or AbstractFormat instead.
 *
 * @see TextBlot
 * @see AbstractFormat
 * @see https://github.com/quilljs/parchment#blots Explanation of the JS implementation of quill (parchment) blots.
 *
 * @package Vanilla\Quill\Blot
 */
abstract class AbstractBlot {

    /** @var string */
    protected $content = "";

    /** @var array  */
    protected $currentOperation = [];

    /** @var array  */
    protected $previousOperation = [];

    /** @var array  */
    protected $nextOperation = [];

    /**
     * Determine if the operations match this Blot type.
     *
     * @param array[] $operations An array of operations to check.
     *
     * @return bool
     */
    abstract public static function matches(array $operations): bool;

    /**
     * Render the blot into an HTML string.
     *
     * @return string
     */
    abstract public function render(): string;

    /**
     * Determine whether or not this Blot should clear the current Group.
     *
     * @param BlotGroup $group
     *
     * @return bool
     */
    abstract public function shouldClearCurrentGroup(BlotGroup $group): bool;

    /**
     * Determine whether or not this blot uses both current and next operation.
     *
     * @return bool
     */
    abstract public function hasConsumedNextOp(): bool;

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
     * Create a blot.
     *
     * @param array $currentOperation The current operation.
     * @param array $previousOperation The next operation. Used to determine closing tags.
     * @param array $nextOperation The previous operation. Used to determine opening tags.
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        $this->previousOperation = $previousOperation;
        $this->currentOperation = $currentOperation;
        $this->nextOperation = $nextOperation;
    }
}
