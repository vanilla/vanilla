<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

/**
 * Instagram Embed.
 */
class InstagramEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['instagram.com', 'http://instagr.am'];

    /**
     * InstagramEmbed constructor.
     */
    public function __construct() {
        parent::__construct('instagram', 'video');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = null;

//        if ($this->isNetworkEnabled()) {
//            $oembed = $this->oembed("https://api.instagram.com/oembed?url=" . urlencode($url));
//            if ($oembed) {
//                $oembed = $this->normalizeOembed($oembed);
//                $data = $oembed;
//            }
//        }

        preg_match(
            '/https?:\/\/(?:www\.)?instagr(?:\.am|am\.com)\/p\/(?<postID>[\w-]+)/i',
            $url,
            $matches
        );
        if (array_key_exists('postID', $matches)) {
            $data = $data ?: [];
            if (!array_key_exists('attributes', $data)) {
                $data['attributes'] = [];
            }
            $data['attributes']['postID'] = $matches['postID'];
        }
            return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {


        $result = <<<HTML
<div class="instagram-video VideoWrap">
   <iframe src="https://instagram.com/p/{$data['attributes']['postID']}/embed/" width="412" height="510" frameborder="0" scrolling="no" allowtransparency="true"></iframe>
HTML;

       return $result;
    }
}
