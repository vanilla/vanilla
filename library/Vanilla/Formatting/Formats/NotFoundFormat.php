<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Formats;

use Garden\StaticCacheTranslationTrait;
use \Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Exception\FormatterNotFoundException;
use Vanilla\Web\TwigRenderTrait;

class NotFoundFormat implements FormatInterface {
    use TwigRenderTrait;
    use StaticCacheTranslationTrait;

    const ERROR_VIEW_LOCATION = 'resources/userContentError';

    /** @var string */
    private $searchedFormat;

    /**
     *
     * @param string $searchedFormat The format that could not be found.
     */
    public function __construct(string $searchedFormat) {
        $this->searchedFormat = $searchedFormat;
    }

    /**
     * @inheritdoc
     */
    public function renderHTML(string $content): string {
        $viewData = [
            'title' => $this->getErrorMessage(),
        ];
        return $this->renderTwig(self::ERROR_VIEW_LOCATION, $viewData);
    }

    /**
     * @inheritdoc
     */
    public function renderExcerpt(string $content, string $query = null): string {
        return $this->getErrorMessage();
    }

    /**
     * @inheritdoc
     */
    public function renderPlainText(string $content): string {
        return $this->getErrorMessage();
    }

    /**
     * @inheritdoc
     */
    public function renderQuote(string $content): string {
        return $this->renderHTML($content);
    }

    /**
     * @inheritdoc
     */
    public function filter(string $content): string {
        throw new FormatterNotFoundException($this->getErrorMessage());
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
        return [];
    }

    /**
     * @inheritdoc
     */
    public function parseMentions(string $content): array {
        return [];
    }

    private function getErrorMessage() {
        return sprintf(self::t('No formatter is installed for the format %s'), $this->searchedFormat);
    }

}
