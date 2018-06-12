<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

/**
 * Instagram Embed.
 */
class ImgurEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['imgur.com'];

    /**
     * ImgurEmbed constructor.
     */
    public function __construct() {
        parent::__construct('imgur', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];

        if ($this->isNetworkEnabled()) {
            preg_match('/https?:\/\/imgur\.com\/gallery\/(?<postID>[a-z0-9]+)/i', $url, $matches);
            if (array_key_exists('postID', $matches)) {
                if (!array_key_exists('attributes', $data)) {
                    $data['attributes'] = [];
                }
                $data['attributes']['postID'] = $matches['postID'];
            } else {
                throw new Exception('Unable to get post ID.', 400);
            }
        }
        // Get the json for th imgur post
        $jsonResponse = $this->httpRequest($url . ".json");
        if ($jsonResponse->getStatusCode() == 200) {
            $decodedResponse = json_decode($jsonResponse);
            $isAlbum = val('is_album', $decodedResponse->data->image);
            if ($isAlbum === null) {
                throw new Exception('Unable to get album.', 400);
            }
            $data['attributes']['isAlbum'] = $isAlbum;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
       $postID = htmlspecialchars($data['attributes']['postID']);
       $dataID = ($data['attributes']['isAlbum']) ? "a/".$postID : $postID;
       $url = htmlspecialchars("https://imgur.com/");

        $result = <<<HTML
<div class="embed embedImgur">
    <blockquote class="imgur-embed-pub" lang="en" data-id="{$dataID}"><a href="{$url}{$postID}"></a></blockquote>
</div>
HTML;
       return $result;
    }
}
