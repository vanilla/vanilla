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
    protected $domains = ['giphy.com','media.giphy.com', 'gph.is'];

    /**
     * giphyEmbed constructor.
     */
    public function __construct() {
        parent::__construct('giphy', 'gif');
    }

    /**
     * @inheritdoc
     */
    public function canHandle(string $domain, string $url = null): bool {
        $result = ($domain == "media.giphy.com") ? true : false;
        return $result;
    }


    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];

        if ($this->isNetworkEnabled()) {
            preg_match(
                '/https?:\/\/([a-zA-Z]+\.)?gi?phy?\.(com|is)\/(([\w]+\/)?(([\w]+-)*+)?(?<postID>[a-zA-Z0-9]+))(\/giphy.gif)?/i',
                $url,
                $post
            );

            if (!$post['postID']) {
                throw new Exception('Unable to get post ID.', 400);
            }

            $oembedData = $this->oembed("https://giphy.com/services/oembed?url=".urlencode($url));
            if ($oembedData) {
                $data = $this->normalizeOembed($oembedData);
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
}
