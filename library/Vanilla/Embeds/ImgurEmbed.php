<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;


use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\PageScraper;

/**
 * Instagram Embed.
 */
class ImgurEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['imgur.com'];

    /** @var PageScraper */
    private $pageScraper;


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
        $pageInfo = [];

        if ($this->isNetworkEnabled()) {
            preg_match('/https?:\/\/imgur\.com\/gallery\/(?<postID>[a-z0-9]+)/i', $url, $matches);
            if (array_key_exists('postID', $matches)) {
                if (!array_key_exists('attributes', $data)) {
                    $data['attributes'] = [];
                }
                $data['attributes']['postID'] = $matches['postID'];
            }
        }

        $jsonResponse = $this->httpRequest($url . ".json");
        if ($jsonResponse->getStatusCode() == 200) {
            $decodedResponse = json_decode($jsonResponse);
            $data['attributes']['isAlbum'] = val('is_album', $decodedResponse->data->image); ;
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
       $postID = htmlspecialchars($data['attributes']['postID']);
       $dataID = ($data['attributes']['isAlbum']) ? "a/".$postID : $postID;

        $result = <<<HTML
        <div class="embed embedImgur">
        <blockquote class="imgur-embed-pub" lang="en" data-id="{$dataID}"><a href="//imgur.com/{$postID}"></a>
        </div>
HTML;
       return $result;
    }
}
