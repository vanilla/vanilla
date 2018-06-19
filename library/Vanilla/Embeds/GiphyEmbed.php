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
        parent::__construct('giphy', 'image');
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

            $oembedData = $this->oembed("https://giphy.com/services/oembed?url=" . urlencode($url));
            if ($oembedData) {
                $data = $this->normalizeOembed($oembedData);
            }

        if ($post) {
                $data['attributes']['postID'] = $post['postID'];
            }
            $data['type'] = "giphy";
            $data['attributes']['url'] = $url;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $height = htmlspecialchars($data['height']) ?? 1;
        $width = htmlspecialchars($data['width']) ?? 1;
        $padding = ($height/$width) * 100;
        $url = "https://giphy.com/embed/".$data['attributes']['postID'];
        $encodedURL = htmlspecialchars($url);

        $result = <<<HTML
<div class="embed embedGiphy" style="width: {$width}px">
    <div class="embedExternal-ratio" style="padding-bottom: {$padding}%">
        <iframe class="giphy-embed embedGiphy-iframe" src="{$encodedURL}"></iframe>
    </div>
</div>
HTML;
        return $result;
    }
}
