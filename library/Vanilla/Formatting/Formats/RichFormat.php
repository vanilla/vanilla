<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Garden\Schema\ValidationException;
use Garden\StaticCacheTranslationTrait;
use Logger;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\Embeds\ErrorEmbed;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\Contracts\Formatting\HeadingProviderInterface;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\ParsableDOMInterface;
use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ParagraphLineTerminatorBlot;
use Vanilla\Formatting\TextDOMInterface;
use Vanilla\Formatting\UserMentionsTrait;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Web\TwigRenderTrait;
use Vanilla\Formatting\Quill;

/**
 * Format service for the rich editor format. Rendered and parsed using Quill.
 *
 * @template-implements FormatInterface<RichFormatParsed>
 */
class RichFormat extends BaseFormat implements ParsableDOMInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use TwigRenderTrait;
    use StaticCacheTranslationTrait;
    use UserMentionsTrait;

    const FORMAT_KEY = "rich";

    /** @var string */
    const RENDER_ERROR_MESSAGE = "There was an error rendering this rich post.";

    /** @var Quill\Parser */
    private $parser;

    /** @var Quill\Renderer */
    private $renderer;

    /** @var Quill\Filterer */
    private $filterer;

    /** @var FormatService */
    private $formatService;

    /** @var EmbedService */
    private $embedService;

    /**
     * Constructor for DI.
     *
     * @param Quill\Parser $parser
     * @param Quill\Renderer $renderer
     * @param Quill\Filterer $filterer
     */
    public function __construct(
        Quill\Parser $parser,
        Quill\Renderer $renderer,
        Quill\Filterer $filterer,
        FormatService $formatService,
        EmbedService $embedService
    ) {
        $this->parser = $parser;
        $this->renderer = $renderer;
        $this->filterer = $filterer;
        $this->formatService = $formatService;
        $this->setLogger(Logger::getLogger());
        $this->embedService = $embedService;
    }

    /**
     * @inheritdoc
     * @return RichFormatParsed
     */
    public function parse(string $content)
    {
        try {
            $content = $this->filterer->filter($content);
            $operations = Quill\Parser::jsonToOperations($content);

            $blotGroups = $this->parser->parse(
                $operations,
                $this->allowExtendedContent ? Quill\Parser::PARSE_MODE_EXTENDED : Quill\Parser::PARSE_MODE_NORMAL
            );
            $parsed = new RichFormatParsed($content, $blotGroups);
            return $parsed;
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return new RichFormatParsed(
                '[{"insert":"' . self::RENDER_ERROR_MESSAGE . '"}]',
                $this->parser->parse([["insert" => self::RENDER_ERROR_MESSAGE]])
            );
        }
    }

    /**
     * Ensure we have parsed content.
     *
     * @param RichFormatParsed|string $rawOrParsed
     *
     * @return RichFormatParsed
     */
    private function ensureParsed($rawOrParsed): RichFormatParsed
    {
        if ($rawOrParsed instanceof RichFormatParsed) {
            return $rawOrParsed;
        } else {
            return $this->parse($rawOrParsed);
        }
    }

    /**
     * Ensure we have raw content.
     *
     * @param RichFormatParsed|string $rawOrParsed
     *
     * @return string
     */
    private function ensureRaw($rawOrParsed): string
    {
        if ($rawOrParsed instanceof RichFormatParsed) {
            return $rawOrParsed->getRawContent();
        } else {
            return $rawOrParsed;
        }
    }

    /**
     * @inheritdoc
     */
    public function renderHTML($content, bool $throw = false): string
    {
        try {
            $blotGroups = $this->ensureParsed($content)->getBlotGroups();
            $html = $this->renderer->render($blotGroups);
            $html = $this->applyHtmlProcessors($html);
            return $html;
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
    public function renderPlainText($content): string
    {
        $text = "";
        try {
            $blotGroups = $this->ensureParsed($content)->getBlotGroups();

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
    public function renderQuote($content): string
    {
        try {
            $blotGroups = $this->ensureParsed($content)->getBlotGroups();
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
    public function filter($content): string
    {
        $content = $this->ensureRaw($content);
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
    private function parseEmbedsOfType(string $content, string $embedClass): array
    {
        $operations = Quill\Parser::jsonToOperations($content);
        $parser = (new Quill\Parser())->addBlot(ExternalBlot::class)->addBlot(ParagraphLineTerminatorBlot::class);
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
    public function parseImageUrls($content): array
    {
        $content = $this->ensureRaw($content);

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
    public function parseImages($content): array
    {
        $content = $this->ensureRaw($content);

        $props = [];
        try {
            $embeds = $this->parseEmbedsOfType($content, ImageEmbed::class);

            /** @var ImageEmbed $imageEmbed */
            foreach ($embeds as $imageEmbed) {
                $props[] = [
                    "url" => $imageEmbed->getUrl(),
                    "alt" => $imageEmbed->getAlt(),
                ];
            }
        } catch (\Throwable $e) {
            $this->logBadInput($e);
        }
        return $props;
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments($content): array
    {
        $content = $this->ensureRaw($content);

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
    public function parseMentions($content, bool $skipTaggedContent = true): array
    {
        $content = $this->ensureRaw($content);

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
    public function parseHeadings($content): array
    {
        $content = $this->ensureRaw($content);

        $outline = [];

        try {
            $operations = Quill\Parser::jsonToOperations($content);
            $parser = (new Quill\Parser())->addBlot(HeadingTerminatorBlot::class)->addBlot(ExternalBlot::class);

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

                if ($blot instanceof ExternalBlot) {
                    $embed = $blot->getEmbed();
                    if ($embed instanceof HeadingProviderInterface) {
                        $outline = array_merge($outline, $embed->getHeadings());
                    }
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
    private function renderErrorMessage(): string
    {
        $data = [
            "title" => self::t(self::RENDER_ERROR_MESSAGE),
            "errorUrl" =>
                "https://docs.vanillaforums.com/help/addons/rich-editor/#why-is-my-published-post-replaced-with-there-was-an-error-rendering-this-rich-post",
        ];

        return $this->renderTwig("resources/views/userContentError.twig", $data);
    }

    /**
     * Trigger an error message for invalid input.
     *
     * @param string $input
     */
    private function logBadInput(string $input)
    {
        ErrorLogger::notice(
            "Malformed rich text encounted.",
            ["formatService"],
            ["input" => $input] + ($this->context ?? [])
        );
    }

    /**
     * Filter a rich body to remove sensitive information.
     *
     * @param array $row The row to filter.
     * @return array Returns the filtered row.
     */
    public static function editBodyFilter($row)
    {
        if (!is_array($row) || strcasecmp($row["format"] ?? ($row["Format"] ?? ""), "rich") !== 0) {
            return $row;
        }

        $key = array_key_exists("Body", $row) ? "Body" : "body";
        $row[$key] = self::stripSensitiveInfoRich($row[$key]);

        return $row;
    }

    /**
     * Strip sensitive user info from a rich string and rewrite it.
     *
     * @param string $input The rich text input.
     * @return string The string.
     */
    private static function stripSensitiveInfoRich(string $input): string
    {
        if (strpos($input, "password") === false) {
            return $input; // Bailout because it doesn't actually have user record.
        }
        $operations = json_decode($input, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($operations)) {
            return $input;
        }
        foreach ($operations as &$op) {
            $insertUser = $op["insert"]["embed-external"]["data"]["insertUser"] ?? null;
            if (!$insertUser) {
                // No user.
                continue;
            }
            $op["insert"]["embed-external"]["data"]["insertUser"] = [
                "userID" => $insertUser["userID"],
                "name" => $insertUser["name"],
                "photoUrl" => $insertUser["photoUrl"],
                "dateLastActive" => $insertUser["dateLastActive"],
                "label" => $insertUser["label"],
            ];
        }
        $output = json_encode($operations, JSON_UNESCAPED_UNICODE);
        return $output;
    }

    /**
     * @inheritDoc
     */
    public function parseDOM(string $content): TextDOMInterface
    {
        $content = $this->filterer->filter($content);
        $operations = Quill\Parser::jsonToOperations($content);

        $blotGroups = $this->parser->parse(
            $operations,
            $this->allowExtendedContent ? Quill\Parser::PARSE_MODE_EXTENDED : Quill\Parser::PARSE_MODE_NORMAL
        );

        return $blotGroups;
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        $operations = Quill\Parser::jsonToOperations($body);

        foreach ($operations as &$op) {
            if (isset($op["insert"]["mention"]["name"])) {
                // Anonymize at-mentions
                if ($username === $op["insert"]["mention"]["name"]) {
                    ArrayUtils::setByPath("insert.mention.name", $op, $this->getAnonymizeUserName());
                    ArrayUtils::setByPath("insert.mention.userID", $op, -1);
                }
            }

            if (isset($op["attributes"]["link"])) {
                // Anonymize profile URLs that are in the href attribute of anchor tags
                $link = ArrayUtils::getByPath("attributes.link", $op);
                $profileLink = \UserModel::getProfileUrl(["name" => $username]);
                if ($link === $profileLink) {
                    ArrayUtils::setByPath("attributes.link", $op, $this->getAnonymizeUserUrl());
                }
            }

            if (isset($op["insert"]) && is_string($op["insert"])) {
                // Anonymize plain-text profile URLs
                [$pattern, $replacement] = $this->getUrlReplacementPattern($username, $this->getAnonymizeUserUrl());
                $op["insert"] = preg_replace($pattern, $replacement, $op["insert"]);
            }

            if (isset($op["insert"]["embed-external"])) {
                // Anonymize mentions in a quote.
                $data = ExternalBlot::getEmbedDataFromOperation($op);
                $embedType = $data["embedType"] ?? null;
                if ($embedType === "quote") {
                    $quoteName = ArrayUtils::getByPath("insertUser.name", $data);
                    if ($quoteName === $username) {
                        ArrayUtils::setByPath("insertUser.userID", $data, -1);
                        ArrayUtils::setByPath("insertUser.name", $data, $this->getAnonymizeUserName());
                        ArrayUtils::setByPath("insertUser.url", $data, $this->getAnonymizeUserUrl());
                    }

                    $format = ArrayUtils::getByPath("format", $data);
                    $bodyRaw = ArrayUtils::getByPath("bodyRaw", $data);
                    if (is_array($bodyRaw)) {
                        $bodyRaw = json_encode($bodyRaw, JSON_UNESCAPED_UNICODE);
                    }
                    $bodyRaw = $this->formatService->removeUserPII($username, $bodyRaw, $format);
                    ArrayUtils::setByPath("bodyRaw", $data, $bodyRaw);

                    $embed = $this->embedService->createEmbedFromData($data);

                    if (!($embed instanceof ErrorEmbed)) {
                        $op["insert"]["embed-external"]["data"] = $embed->getData();
                    }
                }
            }
        }
        return json_encode($operations);
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        $mentions = [];

        $operations = Quill\Parser::jsonToOperations($body);
        foreach ($operations as $op) {
            if (isset($op["insert"]["mention"]["name"])) {
                // Get at-mentions
                $mentions[] = ArrayUtils::getByPath("insert.mention.name", $op);
            }
            if (isset($op["attributes"]["link"])) {
                // Get profile URLs that are in the href attribute of anchor tags
                $matches = [];
                preg_match_all("~{$this->getUrlPattern()}~", $op["attributes"]["link"], $matches);
                $mentions = array_merge($mentions, $this->normalizeMatches($matches, true));
            }
            if (isset($op["insert"]) && is_string($op["insert"])) {
                // Get plain-text profile URLs
                $matches = [];
                preg_match_all("~{$this->getUrlPattern()}~", $op["insert"], $matches);
                $mentions = array_merge($mentions, $this->normalizeMatches($matches, true));
            }
            if (isset($op["insert"]["embed-external"])) {
                // Get mentions in a quote.
                $embedType = ArrayUtils::getByPath("insert.embed-external.data.embedType", $op);
                if ($embedType === "quote") {
                    $prefix = "insert.embed-external.data";

                    $format = ArrayUtils::getByPath("$prefix.format", $op);
                    $bodyRaw = ArrayUtils::getByPath("$prefix.bodyRaw", $op);
                    if (is_array($bodyRaw)) {
                        $bodyRaw = json_encode($bodyRaw, JSON_UNESCAPED_UNICODE);
                    }

                    $quotedMentions = $this->formatService->parseAllMentions($bodyRaw, $format);
                    $mentions[] = ArrayUtils::getByPath("$prefix.insertUser.name", $op);
                    $mentions = array_merge($mentions, $quotedMentions);
                }
            }
        }

        return $mentions;
    }
}
