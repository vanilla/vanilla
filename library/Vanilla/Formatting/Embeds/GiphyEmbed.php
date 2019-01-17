<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Exception;

/**
 * giphy Embed.
 */
class GiphyEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['giphy.com','gph.is'];

    /**
     * GiphyEmbed constructor.
     */
    public function __construct() {
        parent::__construct('giphy', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $domain = parse_url($url, PHP_URL_HOST);

        if ($this->isNetworkEnabled()) {

            $postID = $this->parseURL($url);

            $oembedData = $this->oembed("https://giphy.com/services/oembed?url=".urlencode($url));
            if ($oembedData) {
                $data = $this->normalizeOembed($oembedData);

                // Obtain the real post ID from media url provided by oembed call
                if ($domain == 'gph.is') {
                    $postID = $this->parseURL($data['url']);
                }
            }

            if ($postID) {
                $data['attributes']['postID'] = $postID;
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $height = $data['height'] ?? 1;
        $width = $data['width'] ?? 1;
        $encodedHeight = htmlspecialchars($height);
        $encodedWidth = htmlspecialchars($width);

        if (is_numeric($height) && is_numeric($width)) {
            $padding = ($encodedHeight / $encodedWidth) * 100;
        } else {
            $padding = 100;
        }

        $postID = $data['attributes']['postID'] ?? '';
        $url = "https://giphy.com/embed/".$postID;
        $encodedURL = htmlspecialchars($url);

        $result = <<<HTML
<div class="embedExternal embedGiphy">
    <div class="embedExternal-content" style="width: {$encodedWidth}px">
        <div class="embedExternal-ratio" style="padding-bottom: {$padding}%">
            <iframe class="giphy-embed embedGiphy-iframe" src="{$encodedURL}"></iframe>
        </div>
    </div>
</div>
HTML;
        return $result;
    }

    /**
     * Parses the posted url for the Post ID.
     *
     * @param string $url
     * @return string $postID
     * @throws Exception If post id is not found.
     */
    private function parseURL($url): string {
        preg_match(
            '/https?:\/\/([a-zA-Z]+\.)?gi?phy?\.(com|is)\/(([\w]+\/)?(([\w]+-)*+)?(?<postID>[a-zA-Z0-9]+))(\/giphy.gif)?/i',
            $url,
            $post
        );

        if (!$post['postID']) {
            throw new Exception('Unable to get post ID.', 400);
        }

        $postID  = $post['postID'];

        return $postID;
    }
}
