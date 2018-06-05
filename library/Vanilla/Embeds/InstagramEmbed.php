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
        $data = null;

        if ($this->isNetworkEnabled()) {
            // The oembed is used only to pull the width and height of the object.
            $oembedData = $this->oembed("https://api.instagram.com/oembed?url=" . urlencode($url));
        }
        // extract postID from the instagram post.
        preg_match(
            '/https?:\/\/(?:www\.)?instagr(?:\.am|am\.com)\/p\/(?<postID>[\w-]+)/i',
            $url,
            $matches
        );

        $data = $data ?: [];
        if (array_key_exists('postID', $matches)) {
            if (!array_key_exists('attributes', $data)) {
                $data['attributes'] = [];
            }
            $data['attributes']['postID'] = $matches['postID'];
        }

        if (array_key_exists('width', $oembedData)) {
            $data['width'] = $oembedData['width'] ?? '';
        }

        if (array_key_exists('height', $oembedData)) {
            $data['height'] = $oembedData['height'] ?? '';
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $instagramPostID = $data['attributes']['postID'] ?? '';
        $instagramPostID = htmlspecialchars($instagramPostID);
        $width = $data['width'] ?? 412;
        $height = $data['height'] ?? 510;

        $result = <<<HTML
<div class="embed-image embed embedImage">
   <iframe class="embedImage-img" src="https://instagram.com/p/{$instagramPostID}/embed/"  width="$width" height="$height" frameborder="0" scrolling="no" allowtransparency="true"></iframe>
</div>
HTML;

       return $result;
    }
}
