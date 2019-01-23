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
 * A class for handling formattable text.
 */
class TextBlot extends AbstractBlot {

    use FormattableTextTrait;

    /**
     * The TextBlot can only match on operations that are a plain string insert.
     *
     * @inheritDoc
     */
    public static function matches(array $operation): bool {
        $insert = $operation["insert"] ?? null;
        return is_string($insert);
    }

    /**
     * Parse out text formats, and get main initial content.
     *
     * @inheritDoc
     */
    public function __construct(
        array $currentOperation,
        array $previousOperation,
        array $nextOperation,
        string $parseMode = Parser::PARSE_MODE_NORMAL
    ) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation, $parseMode);
        $this->parseFormats($this->currentOperation, $this->previousOperation, $this->nextOperation);

        // Grab the insert text for the content.
        $this->content = $this->currentOperation["insert"] ?? "";
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        $sanitizedContent = $this->content === "\n" ? "<br>" : htmlentities($this->content, ENT_QUOTES);

        return $this->renderOpeningFormatTags().$sanitizedContent.$this->renderClosingFormatTags();
    }

    /**
     * When a text blot is just a newline, it renders alone - <p><br></p>.
     */
    public function isOwnGroup(): bool {
        return $this->isPlainTextNewLine();
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(BlotGroup $group): bool {
        return $this->isOwnGroup() || $this->isPlainTextNewLine();
    }

    /**
     * Utility function for determining if a group of operations contains a blot with particular attribute.
     *
     * @param array $operation The operations to check.
     * @param string $attrLookupKey The attribute key to lookup.
     * @param mixed $expectedValue A value or array of possible values that the key should be matched against.
     *
     * @return bool
     */
    protected static function opAttrsContainKeyWithValue(
        array $operation,
        string $attrLookupKey,
        $expectedValue = true
    ) {
        $value = valr("attributes.$attrLookupKey", $operation);

        if ((is_array($expectedValue) && in_array($value, $expectedValue)) || $value === $expectedValue) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether or not the blot is a TextBlot directory (not a subclass) and it's content is a newline.
     *
     * @return bool
     */
    private function isPlainTextNewLine(): bool {
        return get_class($this) === TextBlot::class && $this->content === "\n";
    }
}
