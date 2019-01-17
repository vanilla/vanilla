<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

/**
 * Embed for file attachments.
 */
class FileEmbed extends Embed {

    const EMBED_TYPE = "file";

    /**
     * @inheritdoc
     */
    public function __construct() {
        parent::__construct(self::EMBED_TYPE, self::EMBED_TYPE);
    }

    /**
     * No scraping of file embeds is currently enabled.
     *
     * @inheritdoc
     */
    public function canHandle(string $domain, string $url = null): bool {
        return false;
    }

    /**
     * No scraping of file embeds is currently enabled.
     *
     * @inheritdoc
     */
    public function matchUrl(string $url): array {
        return [];
    }

    /**
     * Embed for render
     *
     * @param array $data
     *
     * @return string
     */
    public function renderData(array $data): string {
        $url = $data['url'] ?? "";
        $sanitizedUrl = htmlspecialchars(\Gdn_Format::sanitizeUrl($url));

        // JSON and HTML encode the data so that the react component can mount on this.
        $jsonData = htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8');

        return <<<HTML
<div class="embedExternal">
    <div class="embedExternal-content">
        <div class="js-fileEmbed embedResponsive-initialLink" data-json='$jsonData'><a href="$sanitizedUrl" download>$sanitizedUrl</a></div>
    </div>
</div>
HTML;
    }
}
