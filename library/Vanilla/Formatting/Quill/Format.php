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

    /**
     * Constructor for DI.
     *
     * @param Parser $parser
     * @param Renderer $renderer
     */
    public function __construct(Parser $parser, Renderer $renderer) {
        $this->parser = $parser;
        $this->renderer = $renderer;
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
        $operations = Parser::jsonToOperations($content);
        // Re-encode the value to escape unicode values.
        $this->stripUselessEmbedData($operations);
        $operations = json_encode($operations);
        return $operations;
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

    /**
     * There is certain embed data from the rich editor that we want to strip out. This includes
     *
     * - Malformed partially formed operations (dataPromise).
     * - Nested embed data.
     *
     * @param array[] $operations The quill operations to loop through.
     */
    private function stripUselessEmbedData(array &$operations) {
        foreach($operations as $key => $op) {
            // If a dataPromise is still stored on the embed, that means it never loaded properly on the client.
            $dataPromise = $op['insert']['embed-external']['dataPromise'] ?? null;
            if ($dataPromise !== null) {
                unset($operations[$key]);
            }

            // Remove nested external embed data. We don't want it rendered and this will prevent it from being
            // searched.
            $format = $op['insert']['embed-external']['data']['format'] ?? null;
            if ($format === 'Rich') {
                $bodyRaw = $op['insert']['embed-external']['data']['bodyRaw'] ?? null;
                if (is_array($bodyRaw)) {
                    foreach ($bodyRaw as $subInsertIndex => &$subInsertOp) {
                        $externalEmbed = $operations[$key]['insert']['embed-external']['data']['bodyRaw'][$subInsertIndex]['insert']['embed-external'] ?? null;
                        if ($externalEmbed !== null)  {
                            unset($operations[$key]['insert']['embed-external']['data']['bodyRaw'][$subInsertIndex]['insert']['embed-external']['data']);
                        }
                    }
                }
            }
        }
    }
}
