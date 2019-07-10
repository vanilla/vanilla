<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Schema\Schema;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbedUtils;
use Vanilla\Models\VanillaMediaSchema;

/**
 * Image data object.
 */
class ImageEmbed extends AbstractEmbed {

    const TYPE = "image";

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
        $data = EmbedUtils::ensureDimensions($data);
        $data['size'] = $data['size'] ?? 0;

        $name = $data['name'] ?? null;
        if ($name === null) {
            $data['name'] = basename($data['url']);
        }
        return $data;
    }

    /**
     * Render the image out.
     *
     * @return string
     */
    public function renderHtml(): string {
        $viewPath = dirname(__FILE__) . '/ImageEmbed.twig';
        return $this->renderTwig($viewPath, $this->data);
    }

    /**
     * @inheritdoc
     */
    protected function schema(): Schema {
        return new VanillaMediaSchema(false);
    }
}
