<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

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

        if ($this->isNetworkEnabled()) {
            // The oembed is used only to pull the width and height of the object.
            $oembedData= [];
            $oembedData = $this->oembed("https://giphy.com/services/oembed?url=" . urlencode($url));
            $data =  $oembedData;

            preg_match(
                '/https?:\/\/(www\.)?giphy\.com\/[a-zA-Z0-9]+?\/([a-zA-Z0-9-]+-(?<postID>[a-zA-Z0-9]+))/i',
                $url,
                $post
            );

            if ($post['postID']) {
                $data['attributes']['postID'] = $post['postID'];
            }


        }
        $data['attributes']['url'] = $url;
        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {

        $result = <<<HTML

HTML;

       return $result;
    }
}
