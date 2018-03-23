<?php
/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace Vanilla\Quill\Blots;

use Vanilla\Quill\Blots\Embeds\EmojiBlot;
use Vanilla\Quill\Formats;
use Vanilla\Quill\Group;
use Vanilla\Quill\Renderer;

class TextBlot extends AbstractBlot {

    /** @var array[] */
    private $openingTags = [];

    /** @var array[] */
    private $closingTags = [];

    /**
     * The inline formats to use.
     */
    const FORMATS = [
        Formats\Link::class,
        Formats\Bold::class,
        Formats\Italic::class,
        Formats\Code::class,
        Formats\Strike::class,
    ];

    /**
     * @inheritDoc
     */
    public function __construct(array $currentOperation, array $previousOperation, array $nextOperation) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation);

        $insert = val("insert", $this->currentOperation, "");
        $this->content = \htmlentities($insert, \ENT_QUOTES);

        if (preg_match("/\\n$/", $this->content)) {
            $this->currentOperation[Renderer::GROUP_BREAK_MARKER] = true;
            $this->content = \rtrim($this->content, "\n");
        }
    }


    /**
     * @inheritDoc
     */
    public static function matches(array $operations): bool {
        return \is_string(val("insert", $operations[0]));
    }

    /**
     * @inheritDoc
     */
    public function render(): string {
        foreach(self::FORMATS as $format) {
            if ($format::matches([$this->currentOperation])) {
                /** @var Formats\AbstractFormat $formatInstance */
                $formatInstance = new $format($this->currentOperation, $this->previousOperation, $this->nextOperation);
                $this->openingTags[] = $formatInstance->getOpeningTag();
                $this->closingTags[] = $formatInstance->getClosingTag();
            }
        }

        $this->closingTags = \array_reverse($this->closingTags);

        $result = "";
        foreach($this->openingTags as $tag) {
            $result .= self::renderOpeningTag($tag);
        }

        $result .= $this->createLineBreaks($this->content);
        foreach($this->closingTags as $tag) {
            $result .= self::renderClosingTag($tag);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function shouldClearCurrentGroup(Group $group): bool {
        return \array_key_exists(Renderer::GROUP_BREAK_MARKER, $this->currentOperation);
    }

    /**
     * Render the opening tags for the current blot.
     *
     * @param array $tag - The tag to render.
     * - string $tag
     * - array $attributes
     *
     * @return string;
     */
    private static function renderOpeningTag(array $tag): string {
        $tagName = val("tag", $tag);

        if (!$tagName) {
            return "";
        }

        $result = "<".$tagName;

        /** @var array $attributes */
        $attributes = val("attributes", $tag);
        if ($attributes) {
            foreach ($attributes as $attrKey => $attr) {
                $result .= " $attrKey=\"$attr\"";
            }
        }

        $result .= ">";
        return $result;
    }

    /**
     * Render the closing tags for the current blot.
     *
     * @param array $tag - The tag to render.
     * - string $tag
     * - array $attributes
     *
     * @return string;
     */
    private static function renderClosingTag(array $tag): string {
        $closingTag = val("tag", $tag);

        if (!$closingTag) {
            return "";
        }

        return "</".$closingTag.">";
    }

    /**
     * @inheritDoc
     */
    public function hasConsumedNextOp(): bool {
        return false;
    }

    /**
     * @inheritDoc
     */
    protected function createLineBreaks(string $input): string {
        if ($this->content === "") {
            return "<br>";
        }

        if (\preg_match("/^\\n.+/", $this->content)) {
            return \preg_replace("/^\\n/", "<br></p><p>", $input);
        } else {
            return $input;
        }
    }
}
