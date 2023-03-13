<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Garden\StaticCacheTranslationTrait;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Formatting\UserMentionInterface;
use Vanilla\Web\TwigRenderTrait;

/**
 * Stub format for rendering errors in every output format if content in an unregisterd format is encountered.
 *
 * @template-implements FormatInterface<TextFormatParsed>
 */
class NotFoundFormat implements FormatInterface
{
    use TwigRenderTrait;
    use StaticCacheTranslationTrait;

    const ERROR_VIEW_LOCATION = "resources/views/userContentError.twig";

    /** @var string */
    private $searchedFormat;

    /** @var array context*/
    private $context;

    /**
     * @inheritdoc
     */
    public function parse(string $content)
    {
        return new TextFormatParsed("not-found", $content);
    }

    /**
     * Constructor.
     *
     * @param string $searchedFormat The format that could not be found.
     */
    public function __construct(string $searchedFormat)
    {
        $this->searchedFormat = $searchedFormat;
    }

    /**
     * @inheritdoc
     */
    public function setContext(?array $context): FormatInterface
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function renderHTML($content): string
    {
        $viewData = [
            "title" => $this->getErrorMessage(),
        ];
        return $this->renderTwig(self::ERROR_VIEW_LOCATION, $viewData);
    }

    /**
     * @inheritdoc
     */
    public function renderExcerpt($content, string $query = null): string
    {
        return $this->getErrorMessage();
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText($content): string
    {
        return $this->getErrorMessage();
    }

    /**
     * @inheritdoc
     */
    public function getPlainTextLength($content): int
    {
        return 0;
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
    public function filter($content): string
    {
        throw new FormatterNotFoundException($this->getErrorMessage());
    }

    /**
     * @inheritdoc
     */
    public function parseAttachments($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseHeadings($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImageUrls($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseImages($content): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions($content, bool $skipTaggedContent = true): array
    {
        return [];
    }

    /**
     * Get the error message string.
     *
     * @return string
     */
    private function getErrorMessage(): string
    {
        return sprintf(self::t("No formatter is installed for the format %s"), $this->searchedFormat);
    }

    /**
     * Set the status for extended content.
     *
     * @param bool $extendContent
     */
    public function setAllowExtendedContent(bool $extendContent): void
    {
    }

    /**
     * @inheritdoc
     */
    public function removeUserPII(string $username, string $body): string
    {
        return $body;
    }

    /**
     * @inheritDoc
     */
    public function parseAllMentions($body): array
    {
        return [];
    }
}
