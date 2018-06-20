<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

use Exception;

/**
 * giphy Embed.
 */
class GiphyEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['giphy.com', 'media.giphy.com', 'gph.is'];

    /**
     * giphyEmbed constructor.
     */
    public function __construct() {
        parent::__construct('giphy', 'gif');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $domain = parse_url($url, PHP_URL_HOST);

        if ($this->isNetworkEnabled()) {

            $post = $this->parseURL($url);

            $oembedData = $this->oembed("https://giphy.com/services/oembed?url=".urlencode($url));
            if ($oembedData) {
                $data = $this->normalizeOembed($oembedData);
            }

            // Obtain the real post ID from media url provided by oembed call
            if ($domain == 'gph.is') {
                $post = $this->parseURL($data['url']);
            }

             if ($post) {
                $data['attributes']['postID'] = $post['postID'];
            }
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $height = $data['height'] ?? 1;
        $encodedHeight = htmlspecialchars($height);

        $width = $data['width'] ?? 1;
        $encodedWidth = htmlspecialchars($width);

        $padding = ($encodedHeight/$encodedWidth) * 100;

        $postID = $data['attributes']['postID'] ?? '';
        $url = "https://giphy.com/embed/".$postID;
        $encodedURL = htmlspecialchars($url);

        $result = <<<HTML
<div class="embed embedGiphy" style="width: {$encodedWidth}px">
    <div class="embedExternal-ratio" style="padding-bottom: {$padding}%">
        <iframe class="giphy-embed embedGiphy-iframe" src="{$encodedURL}"></iframe>
    </div>
</div>
HTML;
        return $result;
    }

    /**
     * Parses the posted url for the Post ID.
     *
     * @param string $url
     * @return array $post
     * @throws Exception if post id is not found.
     */
    private function parseURL($url): array {
        preg_match(
            '/https?:\/\/([a-zA-Z]+\.)?gi?phy?\.(com|is)\/(([\w]+\/)?(([\w]+-)*+)?(?<postID>[a-zA-Z0-9]+))(\/giphy.gif)?/i',
            $url,
            $post
        );

        if (!$post['postID']) {
            throw new Exception('Unable to get post ID.', 400);
        }

        return $post;
    }
}
