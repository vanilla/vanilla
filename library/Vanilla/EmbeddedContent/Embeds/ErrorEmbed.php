<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;

/**
 * Embed for codepen.io.
 */
class ErrorEmbed extends AbstractEmbed {
    const TYPE = "error";

    /** @var \Exception */
    private $exception;

    /**
     * Exclude the parent constructor. There is no validation for this embed. Just a warning.
     *
     * @param \Exception $exception The exception that was thrown.
     * @param array $data The data if we have access to it.
     */
    public function __construct(\Exception $exception, array $data = []) {
        // Intentionally not calling the parent.
        $this->exception = $exception;

        // Try to ensure we have some URL and some Data.
        if (($url = $data['url'] ?? null) === null) {
            $data['url'] = t('');
        }

        if (($type = $data['type'] ?? null) === null) {
            $data['type'] = self::TYPE;
        }

        if (debug()) {
            $data['exception'] = $exception->getMessage();
        }

        $this->data = $data;
    }

    /**
     * @inheritdoc
     */
    public function renderHtml(): string {
        $viewPath = dirname(__FILE__) . '/ErrorEmbed.twig';
        return $this->renderTwig($viewPath, [
            'url' => $this->getUrl(),
            'data' => $this->data,
            'errorMessage' => $this->exception->getMessage(),
        ]);
    }


    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return Schema::parse([]);
    }

    /**
     * Stubbed out because we aren't using validation on this one.
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [];
    }
}
