<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Garden\StaticCacheTranslationTrait;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Heading;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Web\Html\TwigRenderTrait;

class Format extends BaseFormat {

    use TwigRenderTrait;
    use StaticCacheTranslationTrait;

    /** @var string */
    const RENDER_ERROR_MESSAGE = 'There was an error rendering this rich post.';

    /** @var Parser */
    private $parser;

    /** @var Renderer */
    private $renderer;

    /** @var Filterer */
    private $filterer;

    /**
     * Constructor for DI.
     *
     * @param Parser $parser
     * @param Renderer $renderer
     * @param Filterer $filterer
     */
    public function __construct(Parser $parser, Renderer $renderer, Filterer $filterer) {
        $this->parser = $parser;
        $this->renderer = $renderer;
        $this->filterer = $filterer;
    }


    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string {
        try {
            $operations = Parser::jsonToOperations($content);
        } catch (FormattingException $e) {
            return $this->renderErrorMessage();
        }

        $blotGroups = $this->parser->parse($operations);
        return $this->renderer->render($blotGroups);
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText(string $content): string {
        $text = '';
        try {
            $operations = Parser::jsonToOperations($content);
        } catch (FormattingException $e) {
            return self::t(self::RENDER_ERROR_MESSAGE);
        }

        $blotGroups = $this->parser->parse($operations);

        /** @var BlotGroup $blotGroup */
        foreach ($blotGroups as $blotGroup) {
            $text .= $blotGroup->getUnsafeText();
        }
        return $text;
    }

    /**
     * @inheritdoc
     */
    public function filter(string $content): string {
        return $this->filterer->filter($content);
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
    public function parseHeadings(string $content): array {
        $outline = [];

        try {
            $operations = Parser::jsonToOperations($content);
        } catch (FormattingException $e) {
            return [];
        }

        $parser = (new Parser())
            ->addBlot(HeadingTerminatorBlot::class);
        $blotGroups = $parser->parse($operations);

        /** @var BlotGroup $blotGroup */
        foreach ($blotGroups as $blotGroup) {
            $blot = $blotGroup->getPrimaryBlot();
            if ($blot instanceof HeadingTerminatorBlot && $blot->getReference()) {
                $outline[] = new Heading(
                    $blotGroup->getUnsafeText(),
                    $blot->getHeadingLevel(),
                    $blot->getReference()
                );
            }
        }
        return $outline;
    }

    /**
     * Render an error message indicating something went wrong.
     *
     * @return string
     */
    private function renderErrorMessage(): string {
        $data = [
            'title' => self::t(self::RENDER_ERROR_MESSAGE),
            'errorUrl' => 'https://docs.vanillaforums.com/help/addons/rich-editor/#why-is-my-published-post-replaced-with-there-was-an-error-rendering-this-rich-post',
        ];

        return $this->renderTwig('resources/userContentError', $data);
    }
}
