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
class CodePenEmbed extends AbstractEmbed {
    const TYPE = "codepen";

    /**
     * @inheritdoc
     */
    protected function getAllowedTypes(): array {
        return [self::TYPE];
    }

    /**
     * @return Schema
     */
    protected function schema(): Schema {
        return Schema::parse([
            'height:i',
            'width:i',
            'name:s',
            'frameSrc:s',
            'cpID:s',
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array {
        $data = EmbedUtils::remapProperties($data, [
            'cpID' => 'attributes.id',
            'frameSrc' => 'attributes.embedUrl',
        ]);
        $data = EmbedUtils::ensureDimensions($data);
        if ($data['name'] === null) {
            $data['name'] = '(Unknown Name)';
        }
        return $data;
    }
}
