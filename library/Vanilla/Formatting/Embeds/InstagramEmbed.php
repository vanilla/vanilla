<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

/**
 * Instagram Embed.
 */
class InstagramEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['instagram.com', 'instagr.am'];

    /**
     * InstagramEmbed constructor.
     */
    public function __construct() {
        parent::__construct('instagram', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $oembedData= [];

        if ($this->isNetworkEnabled()) {
            // The oembed is used only to pull the width and height of the object.
            $oembedData = $this->oembed("https://api.instagram.com/oembed?url=" . urlencode($url)."&omitscript=true");

            if (array_key_exists('html', $oembedData)) {
                $data = $this->parseResponseHtml($oembedData['html']);
            }
        }
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $instagramPermalink = \Gdn_Format::sanitizeUrl($data["attributes"]["permaLink"] ?? "");
        $instgramVersion = $data['attributes']['versionNumber'] ?? '';
        $url = \Gdn_Format::sanitizeUrl($data["url"] ?? "");

        $encodedUrl = htmlspecialchars($url);
        $dataInstgrmPermalink =  htmlspecialchars($instagramPermalink);
        $dataInstgrmVersion = htmlspecialchars($instgramVersion);

        $result = <<<HTML
<div class="embedExternal embedInstagram">
    <div class="embedExternal-content">
        <blockquote class="instagram-media" data-instgrm-captioned data-instgrm-permalink="{$dataInstgrmPermalink}" data-instgrm-version="{$dataInstgrmVersion}">
            <a href="{$encodedUrl}">$encodedUrl</a>
        </blockquote>
    </div>
</div>
HTML;
        return $result;
    }

    /**
     * Parses the oembed repsonse html for permalink and other data.
     *
     * @param string $html
     * @return array $data
     */
    public function parseResponseHtml(string $html): array {
        $data =[];

        preg_match(
            '/data-instgrm-permalink="(?<permalink>https?:\/\/(?:www\.)?instagr(?:\.am|am\.com)\/p\/([\w-]+))/i',
            $html,
            $permalink
        );
        if ($permalink) {
            $data['attributes']['permaLink'] = $permalink['permalink'];
        }

        preg_match('/(?<isCaptioned>data-instgrm-captioned)/i', $html, $isCaptioned);
        if ($isCaptioned) {
            $data['attributes']['isCaptioned'] = true;
        }

        preg_match('/data-instgrm-version="(?<versionNumber>\d+)"/i', $html, $versionNumber);
        if ($versionNumber) {
            $data['attributes']['versionNumber'] = $versionNumber['versionNumber'];
        }

        return $data;
    }
}
