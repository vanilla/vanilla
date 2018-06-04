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
    protected $domains = ['https://www.instagram.com', 'http://instagr.am', 'instagram.com', 'http://instagram.com', 'www.instagram.com'];

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

        if ($this->isNetworkEnabled()) {
            $oembed = $this->oembed("https://api.instagram.com/oembed?url=" . urlencode($url));
            if ($oembed) {
                $oembed = $this->normalizeOembed($oembed);
                $oembedData = $oembed;
            }
        }

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

        $data['attributes']['url'] = $matches[0];
        $data['width'] = $oembedData['width'];
        $data['height'] = $oembedData['height'];

            return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $encodedUrl = htmlspecialchars($data['attributes']['postID']);
        $result = <<<HTML
<div class="instagram-video VideoWrap">
   <iframe src="https://instagram.com/p/{$encodedUrl}/embed/"  width="412" height="510" frameborder="0" scrolling="no" allowtransparency="true"></iframe>
</div>
HTML;

       return $result;
    }
}
