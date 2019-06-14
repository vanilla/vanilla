<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\VanillaMediaSchema;

/**
 * File Embed data object.
 */
class FileEmbed extends AbstractEmbed {

    const TYPE = "file";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array {
        // The legacy file embeds have everything underneath attributes.
        $attributes = $data['attributes'] ?? null;
        if ($attributes !== null) {
            $data = $attributes;
        }

        return $data;
    }

    /**
     * Render the image out.
     *
     * @return string
     */
    public function renderHtml(): string {
        $viewPath = dirname(__FILE__) . '/FileEmbed.twig';
        return $this->renderTwig($viewPath, [
            'url' => $this->getUrl(),
            'name' => $this->data['name'],
            'embedJson' => json_encode($this->data),
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return new VanillaMediaSchema(false);
    }
}
