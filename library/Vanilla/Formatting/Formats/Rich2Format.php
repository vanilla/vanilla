<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Formatting\Formats;

use Exception;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\Heading;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\Formatting\BaseFormat;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\ParsableDOMInterface;
use Vanilla\Formatting\Rich2\Nodes\AbstractNode;
use Vanilla\Formatting\Rich2\Nodes\Anchor;
use Vanilla\Formatting\Rich2\Nodes\External;
use Vanilla\Formatting\Rich2\Nodes\Mention;
use Vanilla\Formatting\Rich2\Nodes\Text;
use Vanilla\Formatting\Rich2\Parser;
use Vanilla\Formatting\TextDOMInterface;
use Vanilla\Formatting\UserMentionsTrait;
use Vanilla\Utility\ArrayUtils;

class Rich2Format extends BaseFormat implements ParsableDOMInterface, FormatInterface
{
    use UserMentionsTrait;

    const FORMAT_KEY = "rich2";

    private Parser $parser;

    /**
     * Rich2Format constructor.
     *
     * @param Parser $parser
     */
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @inheritDoc
     */
    public function renderHTML(string $content): string
    {
        $nodeList = $this->parser->parse($content);
        $html = $nodeList->render();
        return $this->applyHtmlProcessors($html);
    }

    /**
     * @inheritDoc
     */
    public function renderPlainText(string $content): string
    {
        $nodeList = $this->parser->parse($content);
        $text = $nodeList->renderText();
        return trim($text);
    }

    /**
     * @inheritDoc
     */
    public function renderQuote(string $content): string
    {
        $nodeList = $this->parser->parse($content, null, Parser::PARSE_MODE_QUOTE);
        $html = $nodeList->render();
        return $this->applyHtmlProcessors($html);
    }

    /**
     * @inheritDoc
     */
    public function filter(string $content): string
    {
        try {
            $this->renderHtml($content);
        } catch (Exception $e) {
            // Rethrow as a formatting exception with exception chaining.
            throw new FormattingException($e->getMessage(), 500, $e);
        }
        return $content;
    }

    /**
     * @inheritDoc
     */
    public function parseAttachments(string $content): array
    {
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
    }

    /**
     * @inheritDoc
     */
    public function parseHeadings(string $content): array
    {
        $slugCounter = [];
        $headings = [];
        $this->parser->parse($content, function (AbstractNode $node) use (&$headings, &$slugCounter) {
            if ($node instanceof \Vanilla\Formatting\Rich2\Nodes\Heading) {
                $text = $node->renderText();
                $slug = slugify($text);
                $count = $slugCounter[$slug] ?? 0;
                $slugCounter[$slug] = $count + 1;
                $slug .= $count > 0 ? "-$count" : "";

                $headings[] = new Heading($node->renderText(), $node->getLevel(), $slug);
            }
        });
        return $headings;
    }

    /**
     * @inheritDoc
     */
    public function parseImageUrls(string $content): array
    {
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
    }

    /**
     * @inheritDoc
     */
    public function parseImages(string $content): array
    {
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
    }

    /**
     * @inheritDoc
     */
    public function parseMentions(string $content, bool $skipTaggedContent = true): array
    {
        return $this->parseAllMentions($content);
    }

    /**
     * @inheritDoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        $nodeList = $this->parser->parse($body, function (AbstractNode $node) use (&$mentions, $username) {
            if ($node instanceof Mention) {
                if ($node->getUserName() === $username) {
                    $node->setUserName($this->getAnonymizeUserName());
                    $node->setUserID(-1);
                    $node->setUrl($this->getAnonymizeUserUrl());
                }
            }
            if ($node instanceof Anchor) {
                $url = $node->getUrl();
                $profileUrl = \UserModel::getProfileUrl(["name" => $username]);
                if ($url === $profileUrl) {
                    $node->setUrl($this->getAnonymizeUserUrl());
                }
            }
            if ($node instanceof Text) {
                $text = $node->renderText();
                if ($text === $username) {
                    $node->setText($this->getAnonymizeUserName());
                }
                if ($text === "@" . $username) {
                    $node->setText("@" . $this->getAnonymizeUserName());
                }
            }
        });
        return json_encode($nodeList);
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions(string $body): array
    {
        $mentions = [];
        $this->parser->parse($body, function (AbstractNode $node) use (&$mentions) {
            if ($node instanceof Mention) {
                $mentions[] = $node->getUserName();
            }
            if ($node instanceof Anchor) {
                $matches = [];
                preg_match("~{$this->getUrlPattern()}~", $node->getUrl(), $matches);
                if (isset($matches["url_mentions"])) {
                    $mentions[] = $matches["url_mentions"];
                }
            }
        });
        return $mentions;
    }

    /**
     * @inheritDoc
     */
    public function parseDOM(string $content): TextDOMInterface
    {
        $html = $this->renderHtml($content);
        return new HtmlDocument($html);
    }
}
