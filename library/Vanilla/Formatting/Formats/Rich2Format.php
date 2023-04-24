<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Formats;

use Garden\StaticCacheTranslationTrait;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\Contracts\Formatting\HeadingProviderInterface;
use Vanilla\EmbeddedContent\Embeds\ErrorEmbed;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\ParsableDOMInterface;
use Vanilla\Formatting\Rich2\NodeList;
use Vanilla\Formatting\Rich2\Nodes\AbstractNode;
use Vanilla\Formatting\Rich2\Nodes\Anchor;
use Vanilla\Formatting\Rich2\Nodes\External;
use Vanilla\Formatting\Rich2\Nodes\Mention;
use Vanilla\Formatting\Rich2\Nodes\Paragraph;
use Vanilla\Formatting\Rich2\Nodes\Text;
use Vanilla\Formatting\Rich2\Parser;
use Vanilla\Formatting\TextDOMInterface;
use Vanilla\Formatting\UserMentionsTrait;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Utility\ArrayUtils;

class Rich2Format extends BaseFormat implements ParsableDOMInterface, FormatInterface
{
    use StaticCacheTranslationTrait;
    use UserMentionsTrait;

    const FORMAT_KEY = "rich2";

    /** @var string */
    const RENDER_ERROR_MESSAGE = "There was an error rendering this rich post.";

    private Parser $parser;

    private EmbedService $embedService;

    private FormatService $formatService;

    /**
     * Rich2Format constructor.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser, EmbedService $embedService, FormatService $formatService)
    {
        $this->parser = $parser;
        $this->embedService = $embedService;
        $this->formatService = $formatService;
    }

    /**
     * @inheritdoc
     */
    public function parse(string $content)
    {
        try {
            $nodeList = $this->parser->parse($content);
            return new Rich2FormatParsed(json_encode($nodeList), $nodeList);
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            $paragraph = new Paragraph(["type" => "paragraph"]);
            $paragraph->addChild(new Text(["text" => self::t(self::RENDER_ERROR_MESSAGE)]));
            return new Rich2FormatParsed(
                '[{"type":"paragraph","children":[{"text":"' . self::t(self::RENDER_ERROR_MESSAGE) . '"}]}]',
                new NodeList($paragraph)
            );
        }
    }

    /**
     * Ensure that raw content is turned into a parsed nodelist.
     *
     * @param Rich2FormatParsed|string $rawOrParsed
     *
     * @return Rich2FormatParsed
     */
    private function ensureParsed($rawOrParsed): Rich2FormatParsed
    {
        if ($rawOrParsed instanceof Rich2FormatParsed) {
            return $rawOrParsed;
        } else {
            return $this->parse($rawOrParsed);
        }
    }

    /**
     * Ensure that parsed node lists are turned into raw content.
     *
     * @param Rich2FormatParsed|string $rawOrParsed
     *
     * @return string
     */
    private function ensureRaw($rawOrParsed): string
    {
        if ($rawOrParsed instanceof Rich2FormatParsed) {
            return $rawOrParsed->getRawContent();
        } else {
            return $rawOrParsed;
        }
    }

    /**
     * @inheritDoc
     */
    public function renderHTML($content): string
    {
        try {
            $nodeList = $this->ensureParsed($content)->getNodeList();
            $html = $nodeList->render();
            return $this->applyHtmlProcessors($html);
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return $this->renderErrorMessage();
        }
    }

    /**
     * @inheritDoc
     */
    public function renderPlainText($content): string
    {
        try {
            $nodeList = $this->ensureParsed($content)->getNodeList();
            $text = $nodeList->renderText();
            return trim($text);
        } catch (\Throwable $e) {
            $this->logBadInput($e);
            return self::t(self::RENDER_ERROR_MESSAGE);
        }
    }

    /**
     * @inheritDoc
     */
    public function renderQuote($content): string
    {
        try {
            $content = $this->ensureRaw($content);
            $nodeList = $this->parser->parse($content, null, Parser::PARSE_MODE_QUOTE);
            $html = $nodeList->render();
            return $this->applyHtmlProcessors($html);
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return $this->renderErrorMessage();
        }
    }

    /**
     * @inheritDoc
     */
    public function filter($content): string
    {
        try {
            $this->ensureParsed($content);
        } catch (\Throwable $e) {
            // Rethrow as a formatting exception with exception chaining.
            throw new FormattingException($e->getMessage(), $e->getCode(), $e);
        }
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function parseAttachments($content): array
    {
        try {
            $content = $this->ensureRaw($content);
            $attachments = [];
            $this->parser->parse($content, function (AbstractNode $node) use (&$attachments) {
                if (!($node instanceof External)) {
                    return;
                }
                $embed = $node->getEmbed();
                if ($embed instanceof FileEmbed) {
                    $attachments[] = $embed->asAttachment();
                }
            });
            return $attachments;
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function parseHeadings($content): array
    {
        try {
            $content = $this->ensureRaw($content);
            $headings = [];
            $this->parser->parse($content, function (AbstractNode $node) use (&$headings) {
                if ($node instanceof \Vanilla\Formatting\Rich2\Nodes\Heading) {
                    $headings[] = new Heading($node->renderText(), $node->getLevel(), $node->getRef());
                }
                if ($node instanceof External) {
                    $embed = $node->getEmbed();
                    if ($embed instanceof HeadingProviderInterface) {
                        $headings = array_merge($headings, $embed->getHeadings());
                    }
                }
            });
            return $headings;
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function parseImageUrls($content): array
    {
        try {
            $content = $this->ensureRaw($content);
            $imageUrls = [];
            $this->parser->parse($content, function (AbstractNode $node) use (&$imageUrls) {
                if (!($node instanceof External)) {
                    return;
                }
                $embed = $node->getEmbed();
                if ($embed instanceof ImageEmbed) {
                    $imageUrls[] = $embed->getUrl();
                }
            });
            return $imageUrls;
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function parseImages($content): array
    {
        try {
            $content = $this->ensureRaw($content);
            $images = [];
            $this->parser->parse($content, function (AbstractNode $node) use (&$images) {
                if (!($node instanceof External)) {
                    return;
                }
                $embed = $node->getEmbed();
                if ($embed instanceof ImageEmbed) {
                    $images[] = [
                        "url" => $embed->getUrl(),
                        "alt" => $embed->getAlt(),
                    ];
                }
            });
            return $images;
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function parseMentions($content, bool $skipTaggedContent = true): array
    {
        try {
            $content = $this->ensureRaw($content);
            $mentions = [];
            $this->parser->parse($content, function (AbstractNode $node) use (&$mentions) {
                if ($node instanceof Mention) {
                    // Get at-mentions
                    $mentions[] = $node->getUserName();
                }
                if ($node instanceof External) {
                    // We only need the author referenced by a quote, mentions inside the quote block are ignored
                    $embed = $node->getEmbed();
                    if ($embed instanceof QuoteEmbed) {
                        $mentions[] = $embed->getUsername();
                    }
                }
            });
            return $mentions;
        } catch (\Throwable $e) {
            $this->logBadInput($content);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        $nodeList = $this->parser->parse($body, function (AbstractNode $node) use ($username) {
            if ($node instanceof Mention) {
                // Anonymize at-mentions
                if ($node->getUserName() === $username) {
                    $node->setUserName($this->getAnonymizeUserName());
                    $node->setUserID(-1);
                    $node->setUrl($this->getAnonymizeUserUrl());
                }
            }
            if ($node instanceof Anchor) {
                // Anonymize profile URLs that are in the href attribute of anchor tags
                $url = $node->getUrl();
                $profileUrl = \UserModel::getProfileUrl(["name" => $username]);
                if ($url === $profileUrl) {
                    $node->setUrl($this->getAnonymizeUserUrl());
                }
            }
            if ($node instanceof Text) {
                // Anonymize plain-text profile URLs
                $text = $node->renderTextContent();
                [$pattern, $replacement] = $this->getUrlReplacementPattern($username, $this->getAnonymizeUserUrl());
                $text = preg_replace($pattern, $replacement, $text);
                $node->setText($text);
            }
            if ($node instanceof External) {
                // Anonymize mentions in a quote.
                $data = $node->getEmbedData();
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
                        $node->setEmbed($embed);
                    }
                }
            }
        });
        return json_encode($nodeList);
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        try {
            $body = $this->ensureRaw($body);
            $mentions = [];
            $this->parser->parse($body, function (AbstractNode $node) use (&$mentions) {
                if ($node instanceof Mention) {
                    // Get at-mentions
                    $mentions[] = $node->getUserName();
                }
                if ($node instanceof Anchor) {
                    // Get profile URLs that are in the href attribute of anchor tags
                    $matches = [];
                    preg_match_all("~{$this->getUrlPattern()}~", $node->getUrl(), $matches);
                    $mentions = array_merge($mentions, $this->normalizeMatches($matches, true));
                }
                if ($node instanceof Text) {
                    // Get plain-text profile URLs
                    $matches = [];
                    preg_match_all("~{$this->getUrlPattern()}~", $node->renderTextContent(), $matches);
                    $mentions = array_merge($mentions, $this->normalizeMatches($matches, true));
                }
                if ($node instanceof External) {
                    // Get mentions in a quote.
                    $data = $node->getEmbedData();
                    $embedType = $data["embedType"] ?? null;
                    if ($embedType === "quote") {
                        $format = ArrayUtils::getByPath("format", $data);
                        $bodyRaw = ArrayUtils::getByPath("bodyRaw", $data);
                        if (is_array($bodyRaw)) {
                            $bodyRaw = json_encode($bodyRaw, JSON_UNESCAPED_UNICODE);
                        }

                        $quotedMentions = $this->formatService->parseAllMentions($bodyRaw, $format);
                        $mentions[] = ArrayUtils::getByPath("insertUser.name", $data);
                        $mentions = array_merge($mentions, $quotedMentions);
                    }
                }
            });
            return $mentions;
        } catch (\Throwable $e) {
            $this->logBadInput($body);
            return [];
        }
    }

    /**
     * @inheritDoc
     */
    public function parseDOM(string $content): TextDOMInterface
    {
        $nodeList = $this->parser->parse($content);
        return $nodeList;
    }

    /**
     * Render an error message indicating something went wrong.
     *
     * @return string
     */
    private function renderErrorMessage(): string
    {
        return "<p>" . self::RENDER_ERROR_MESSAGE . "</p>";
    }

    /**
     * Trigger an error message for invalid input.
     *
     * @param string $input
     */
    private function logBadInput(string $input)
    {
        ErrorLogger::notice(
            "Malformed rich2 text encountered.",
            ["formatService"],
            ["input" => $input] + ($this->context ?? [])
        );
    }
}
