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
    protected $domains = ['giphy.com','media.giphy.com', 'gph.com'];

    /**
     * giphyEmbed constructor.
     */
    public function __construct() {
        parent::__construct('giphy', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];
        $post =[];

        if ($this->isNetworkEnabled()) {
            preg_match(
                '/https?:\/\/([a-zA-Z]+\.)?giphy\.com\/[\w]+?\/(([\w]+-)*+(?<postID>[a-zA-Z0-9]+))?(\/giphy.gif)?/i',
                $url,
                $post
            );

            if (!$post['postID']) {
                throw new Exception('Unable to get post ID.', 400);
            }

            $oembedData = $this->oembed("https://giphy.com/services/oembed?url=" . urlencode($url));
            if ($oembedData) {
                $data = $oembedData;
            }
        }
        if ($post) {
            $data['attributes']['postID'] = $post['postID'];
        }

        $data['attributes']['url'] = $url;

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {

        $padding = ($data['height']/$data['width'])* 100;
        $url = "https://giphy.com/embed/".$data['attributes']['postID'];
        $encodedURL = htmlspecialchars($url);

        $result = <<<HTML
<div class="embed embedGiphy" style="width: {$data['width']}px">
<div class="embedExternal-ratio" style="padding-bottom: {$padding}%">
    <iframe class="giphy-embed embedGiphy-iframe" src="{$url}"></a></iframe>
</div>
HTML;
        return $result;
    }
}
