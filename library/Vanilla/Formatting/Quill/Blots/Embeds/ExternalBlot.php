<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill\Blots\Embeds;

use Gdn;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Quill\Blots\AbstractBlot;
use Vanilla\Formatting\Quill\Parser;

/**
 * Blot for rendering embeds with the embed manager.
 */
class ExternalBlot extends AbstractBlot {

    /** @var EmbedService */
    private $embedService;

    /**
     * @inheritDoc
     */
    public static function matches(array $operation): bool {
        return (boolean) valr("insert.embed-external", $operation);
    }

    /**
     * @inheritdoc
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function __construct(
        array $currentOperation,
        array $previousOperation,
        array $nextOperation,
        string $parseMode = Parser::PARSE_MODE_NORMAL
    ) {
        parent::__construct($currentOperation, $previousOperation, $nextOperation, $parseMode);
        $this->content = $this->currentOperation["insert"]["embed-external"]['data']['url'] ?? '';
        if ($this->content !== '') {
            $this->content .= "\n";
        }
        $this->embedService = Gdn::getContainer()->get(EmbedService::class);
    }

    /**
     * Get the embed data.
     *
     * @return array
     */
    public function getEmbedData(): array {
        return $this->currentOperation["insert"]["embed-external"]["data"] ?? [];
    }

    /**
     * Render out the content of the blot using the EmbedService.
     * @inheritDoc
     */
    public function render(): string {
        if ($this->parseMode === Parser::PARSE_MODE_QUOTE) {
            return $this->renderQuote();
        }

        $value = $this->currentOperation["insert"]["embed-external"] ?? [];
        $data = $value['data'] ?? $value;
        try {
            return $this->embedService->createEmbedFromData($data)->renderHtml();
        } catch (\Exception $e) {
            // TODO: Add better error handling here.
            return '';
        }
    }

    public function renderQuote(): string {
        $value = $this->currentOperation["insert"]["embed-external"] ?? [];
        $data = $value['data'] ?? $value;

        $url = $data['url'] ?? "";
        if ($url) {
            $sanitizedUrl = htmlspecialchars(\Gdn_Format::sanitizeUrl($url));
            return "<div class=\"userContent\"><p><a href=\"$sanitizedUrl\">$sanitizedUrl</a></p></div>";
        }
        return "";
    }

    /**
     * Block embeds are always their own group.
     * @inheritDoc
     */
    public function isOwnGroup(): bool {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getGroupOpeningTag(): string {
        return "";
    }

    /**
     * @inheritDoc
     */
    public function getGroupClosingTag(): string {
        return "";
    }
}
