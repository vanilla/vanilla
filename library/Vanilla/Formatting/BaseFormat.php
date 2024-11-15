<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\HtmlProcessor;

/**
 * Base format with simple simple implementations.
 */
abstract class BaseFormat implements FormatInterface
{
    use UserMentionsTrait;

    /** @var int */
    const EXCERPT_MAX_LENGTH = 325;

    /** @var bool */
    protected $allowExtendedContent = false;

    /** @var HtmlProcessor[] */
    protected $staticProcessors = [];

    /** @var HtmlProcessor[] */
    protected $dynamicProcessors = [];

    /** @var array context */
    protected $context;

    /**
     * Apply an HTML processor to the stack of processors.
     *
     * @param HtmlProcessor $processor
     *
     * @return $this For chaining.
     */
    public function addHtmlProcessor(HtmlProcessor $processor): BaseFormat
    {
        if ($processor->getProcessorType() === HtmlProcessor::TYPE_DYNAMIC) {
            $this->dynamicProcessors[] = $processor;
        } else {
            $this->staticProcessors[] = $processor;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setContext(?array $context = []): FormatInterface
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Apply the registered HTML processors.
     *
     * @param string $html The HTML to apply processors to.
     * @param string|null $processorType The type of HTML processors to apply. See HtmlProcessor::TYPE constants.
     * @return string The processed HTML.
     */
    public function applyHtmlProcessors(string $html, ?string $processorType = null): string
    {
        $document = new HtmlDocument($html);

        if ($processorType === HtmlProcessor::TYPE_STATIC || $processorType === null) {
            foreach ($this->staticProcessors as $processor) {
                $document = $processor->processDocument($document);
            }
        }

        if ($processorType === HtmlProcessor::TYPE_DYNAMIC || $processorType === null) {
            foreach ($this->dynamicProcessors as $processor) {
                $document = $processor->processDocument($document);
            }
        }

        return $document->getInnerHtml();
    }

    /**
     * Implement rendering of excerpts based on the plain-text version of format.
     *
     * @inheritdoc
     */
    public function renderExcerpt($content, ?int $length = null): string
    {
        if (!$length) {
            $length = self::EXCERPT_MAX_LENGTH;
        }
        $plainText = $this->renderPlainText($content);

        $excerpt = mb_ereg_replace("\n", " ", $plainText);
        $excerpt = mb_ereg_replace("\s{2,}", " ", $excerpt);
        if (mb_strlen($excerpt) > $length) {
            $excerpt = mb_substr($excerpt, 0, $length);
            if ($lastSpace = mb_strrpos($excerpt, " ")) {
                $excerpt = mb_substr($excerpt, 0, $lastSpace);
            }
            $excerpt .= "…";
        }
        return $excerpt;
    }

    /**
     * @inheritdoc
     */
    public function renderQuote($content): string
    {
        return $this->renderHTML($content);
    }

    /**
     * @inheritdoc
     */
    public function getPlainTextLength($content): int
    {
        return mb_strlen(trim($this->renderPlainText($content), "UTF-8"));
    }

    /**
     * Set the status for extended content.
     *
     * @param bool $extendContent
     */
    public function setAllowExtendedContent(bool $extendContent): void
    {
        $this->allowExtendedContent = $extendContent;
    }

    /**
     * Return the anonymize username.
     *
     * @return string
     */
    public function getAnonymizeUserName(): string
    {
        return t("[Deleted User]");
    }

    /**
     * Return the anonymize User url.
     *
     * @return string
     */
    public function getAnonymizeUserUrl(): string
    {
        return \UserModel::getProfileUrl(["name" => t("[Deleted User]")]);
    }

    /**
     * @inheritDoc
     */
    abstract public function removeUserPII(string $username, string $body): string;

    /**
     * @inheritDoc
     */
    abstract public function parseAllMentions($body): array;

    /**
     * Helper method to parse mentions for non-rich formats.
     *
     * @param string $body
     * @param array $patterns An extra array of patterns to check for.
     * @return string[]
     */
    protected function getNonRichMentions(string $body, array $patterns = []): array
    {
        $patterns[] = $this->getNonRichAtMention();

        $matches = [];
        preg_match_all("~" . $this->getUrlPattern() . "~", $body, $matches, PREG_UNMATCHED_AS_NULL);
        $urlMatches = $this->normalizeMatches($matches, true);

        $matches = [];
        $pattern = "~" . implode("|", $patterns) . "~";
        preg_match_all($pattern, $body, $matches, PREG_UNMATCHED_AS_NULL);
        $otherMatches = $this->normalizeMatches($matches);

        return array_merge($urlMatches, $otherMatches);
    }
}
