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
            "height:i",
            "width:i",
            "name:s?",
            "codePenID:s",
            "author:s",
        ]);
    }

    /**
     * @inheritdoc
     */
    public function normalizeData(array $data): array {
        $legacyUrl = $data["attributes"]["embedUrl"] ?? null;
        if ($legacyUrl && $legacyIDs = $this->urlToIDs($legacyUrl)) {
            $data = array_merge($data, $legacyIDs);
        }

        $data = EmbedUtils::ensureDimensions($data);

        return $data;
    }

    /**
     * Given a CodePen embed URL, attempt to retrieve the author and pen IDs.
     *
     * @param string $url
     * @return string|null
     */
    private function urlToIDs(string $url): ?array {
        if (!preg_match("`/?(?<author>[\w-]+)/embed/(?:preview/)?(?<codePenID>[\w-]+)`", parse_url($url, PHP_URL_PATH) ?? "", $matches)) {
            return null;
        }
        return [
            "author" => $matches["author"],
            "codePenID" => $matches["codePenID"],
        ];
    }
}
