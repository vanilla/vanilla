<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Garden\Schema\ValidationException;
use Garden\StaticCacheTranslationTrait;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\Formatting\Attachment;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Formatting\Quill;

/**
 * Format service for the rich editor format. Rendered and parsed using Quill.
 */
class RichFormat extends BaseFormat {

    use TwigRenderTrait;
    use StaticCacheTranslationTrait;

    const FORMAT_KEY = "rich";

    /** @var string */
    const RENDER_ERROR_MESSAGE = 'There was an error rendering this rich post.';

    /** @var Quill\Parser */
    private $parser;

    /** @var Quill\Renderer */
    private $renderer;

    /** @var Quill\Filterer */
    private $filterer;

    /**
     * Constructor for DI.
     *
     * @param Quill\Parser $parser
     * @param Quill\Renderer $renderer
     * @param Quill\Filterer $filterer
     */
    public function __construct(Quill\Parser $parser, Quill\Renderer $renderer, Quill\Filterer $filterer) {
        $this->parser = $parser;
        $this->renderer = $renderer;
        $this->filterer = $filterer;
    }


    /**
     * @inheritdoc
     */
    public function renderHTML(string $content, bool $throw = false): string {
        try {
            $content = $this->filterer->filter($content);
            $operations = Quill\Parser::jsonToOperations($content);
            $blotGroups = $this->parser->parse($operations);
            return $this->renderer->render($blotGroups);
        } catch (\Throwable $e) {
            $this->logBadInput($e);
            if ($throw) {
                throw new FormattingException($e->getMessage(), $e->getCode(), $e);
            } else {
                return $this->renderErrorMessage();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText(string $content): string {
        $text = '';
        try {
            $content = $this->filterer->filter($content);
            $operations = Quill\Parser::jsonToOperations($content);
            $blotGroups = $this->parser->parse($operations);

            /** @var Quill\BlotGroup $blotGroup */
            foreach ($blotGroups as $blotGroup) {
                $text .= $blotGroup->getUnsafeText();
            }
            return trim($text);
        } catch (\Throwable $e) {
            $this->logBadInput($e);
            return self::t(self::RENDER_ERROR_MESSAGE);
        }
    }

    /**
     * @inheritdoc
     */
    public function renderQuote(string $content): string {
        try {
            $content = $this->filterer->filter($content);
            $operations = Quill\Parser::jsonToOperations($content);
            $blotGroups = $this->parser->parse($operations, Quill\Parser::PARSE_MODE_QUOTE);
            $rendered = $this->renderer->render($blotGroups);

            // Trim out breaks and empty paragraphs.
            $result = str_replace("<p><br></p>", "", $rendered);
            $result = str_replace("<p></p>", "", $result);
            return $result;
        } catch (\Throwable $e) {
            $this->logBadInput($e);
            return $this->renderErrorMessage();
        }
    }

    /**
     * @inheritdoc
     */
    public function filter(string $content): string {
        $filtered = $this->filterer->filter($content);
        $this->renderHTML($filtered, true);
        return $filtered;
    }

    /**
     * Parse out all embeds of a particular type.
     *
     * @param string $content
     * @param string $embedClass
     *
     * @return AbstractEmbed[]
     */
    private function parseEmbedsOfType(string $content, string $embedClass): array {
        $operations = Quill\Parser::jsonToOperations($content);
        $parser = (new Quill\Parser())
            ->addBlot(ExternalBlot::class);
        $blotGroups = $parser->parse($operations);

        $embeds = [];
        /** @var Quill\BlotGroup $blotGroup */
        foreach ($blotGroups as $blotGroup) {
            $blot = $blotGroup->getMainBlot();
            if ($blot instanceof ExternalBlot) {
                try {
                    $embed = $blot->getEmbed();
                    if (is_a($embed, $embedClass)) {
                        $embeds[] = $embed;
                    }
                } catch (ValidationException $e) {
                    continue;
                }
            }
        }

        return $embeds;
    }

    /**
     * @inheritdoc
     */
    public function parseImageUrls(string $content): array {
        $urls = [];

        try {
            $embeds = $this->parseEmbedsOfType($content, ImageEmbed::class);

            /** @var ImageEmbed $imageEmbed */
            foreach ($embeds as $imageEmbed) {
                $urls[] = $imageEmbed->getUrl();
            }
        } catch (\Throwable $e) {
            $this->logBadInput($e);
        }
        return $urls;
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments(string $content): array {
        $attachments = [];

        try {
            $embeds = $this->parseEmbedsOfType($content, FileEmbed::class);

            /** @var FileEmbed $fileEmbed */
            foreach ($embeds as $fileEmbed) {
                $attachments[] = $fileEmbed->asAttachment();
            }
        } catch (\Throwable $e) {
            $this->logBadInput($e);
        }
        return $attachments;
    }


    /**
     * @inheritdoc
     */
    public function parseMentions(string $content): array {
        try {
            $operations = Quill\Parser::jsonToOperations($content);
            return $this->parser->parseMentionUsernames($operations);
        } catch (\Throwable $e) {
            $this->logBadInput($e);
            return [];
        }
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings(string $content): array {
        $outline = [];

        try {
            $operations = Quill\Parser::jsonToOperations($content);
            $parser = (new Quill\Parser())
                ->addBlot(HeadingTerminatorBlot::class);
            $blotGroups = $parser->parse($operations);

            /** @var Quill\BlotGroup $blotGroup */
            foreach ($blotGroups as $blotGroup) {
                $blot = $blotGroup->getMainBlot();
                if ($blot instanceof HeadingTerminatorBlot && $blot->getReference()) {
                    $outline[] = new Heading(
                        $blotGroup->getUnsafeText(),
                        $blot->getHeadingLevel(),
                        $blot->getReference()
                    );
                }
            }
            return $outline;
        } catch (\Throwable $e) {
            $this->logBadInput($e);
            return [];
        }
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

        return $this->renderTwig('resources/views/userContentError.twig', $data);
    }

    /**
     * Trigger an error message for invalid input.
     *
     * @param string $input
     */
    private function logBadInput(string $input) {
        trigger_error(
            errorMessage(
                "Bad input encountered",
                self::class,
                __METHOD__,
                $input
            ),
            E_USER_WARNING
        );
    }

    /**
     * Filter a rich body to remove sensitive information.
     *
     * @param array $row The row to filter.
     * @return array Returns the filtered row.
     */
    public static function editBodyFilter($row) {
        if (!is_array($row) || strcasecmp($row['format'] ?? $row['Format'] ?? '', 'rich') !== 0) {
            return $row;
        }

        $key = array_key_exists('Body', $row) ? 'Body' : 'body';
        $row[$key] = self::stripSensitiveInfoRich($row[$key]);

        return $row;
    }

    /**
     * Strip sensitive user info from a rich string and rewrite it.
     *
     * @param string $input The rich text input.
     * @return string The string.
     */
    private static function stripSensitiveInfoRich(string $input): string {
        if (strpos($input, "password") === false) {
            return $input; // Bailout because it doesn't actually have user record.
        }
        $operations = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($operations)) {
            return $input;
        }
        foreach ($operations as &$op) {
            $insertUser = $op['insert']['embed-external']['data']['insertUser'] ?? null;
            if (!$insertUser) {
                // No user.
                continue;
            }
            $op['insert']['embed-external']['data']['insertUser'] = [
                'userID' => $insertUser['userID'],
                'name' => $insertUser['name'],
                'photoUrl' => $insertUser['photoUrl'],
                'dateLastActive' => $insertUser['dateLastActive'],
                'label' => $insertUser['label'],
            ];
        }
        $output = json_encode($operations, JSON_UNESCAPED_UNICODE);
        return $output;
    }
}
