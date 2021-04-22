<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\FormatConfig;
use Vanilla\Formatting\FormatText;
use Vanilla\Formatting\ParsableDOMInterface;
use Vanilla\Formatting\TextDOMInterface;
use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;

/**
 * Basic display format.
 *
 * The "display" format is a legacy name from OG Vanilla that essentially means `htmlspecialchars`. This format is a
 * quasi-implmentation of Gdn_Format::display.
 */
final class DisplayFormat extends BaseFormat implements ParsableDOMInterface {

    const FORMAT_KEY = "display";

    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string {
        $result = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
        $result = str_replace(["&quot;", "&amp;"], ['"', '&'], $result);
        $result = $this->applyHtmlProcessors($result);
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText(string $content): string {
        return trim($content);
    }

    /**
     * @inheritdoc
     */
    public function filter(string $content): string {
        return $content;
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImageUrls(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImages(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions(string $content): array {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseDOM(string $content): TextDOMInterface {
        return new class($content, $this) implements TextDOMInterface, TextFragmentInterface {

            /** @var DisplayFormat */
            private $parent;

            /** @var string */
            private $text;

            /**
             * Setup the DOM.
             *
             * @param string $text
             * @param DisplayFormat $parent
             */
            public function __construct(string $text, DisplayFormat $parent) {
                $this->parent = $parent;
                $this->text = $text;
            }

            /**
             * @inheritdoc
             */
            public function stringify(): FormatText {
                return new FormatText($this->text, DisplayFormat::FORMAT_KEY);
            }

            /**
             * @inheritdoc
             */
            public function renderHTML(): string {
                return $this->parent->renderHtml($this->text);
            }

            /**
             * @inheritdoc
             */
            public function getFragments(): array {
                return [$this];
            }

            /**
             * @inheritdoc
             */
            public function getInnerContent(): string {
                return $this->text;
            }

            /**
             * @inheritdoc
             */
            public function setInnerContent(string $text) {
                $this->text = $text;
            }

            /**
             * @inheritdoc
             */
            public function getFragmentType(): string {
                return TextFragmentType::TEXT;
            }
        };
    }
}
