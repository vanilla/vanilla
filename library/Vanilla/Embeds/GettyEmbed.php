<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Embeds;

use Exception;
/**
 * Getty Embed.
 */
class GettyEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['gettyimages.ca', 'gty.im', 'gettyimages.com'];

    /**
     * GettyEmbed constructor.
     */
    public function __construct() {
        parent::__construct('getty', 'image');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = [];

        if ($this->isNetworkEnabled()) {
            preg_match(
                '/https?:\/\/www\.gettyimages\.(com|ca).*?(?<postID>\d+)(?!.*\d)/i',
                $url,
                $post
            );
            if (!$post) {
                throw new Exception('Unable to get post ID.', 400);
            }

            $oembedData = $this->oembed("http://embed.gettyimages.com/oembed?url=http://gty.im/".$post['postID']);

            if ($oembedData) {
                $data = $oembedData;
            }
            $data['attributes'] = $this->parseResponseHtml($data['html']);

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
        $url = "//www.gettyimages.com/detail/".$data['attributes']['postID'];
        $encodedURL = htmlspecialchars($url);

        array_walk_recursive($data, function(&$row) {
            $row = htmlspecialchars($row);
        });
        $encodedData = json_encode($data);

        $result = <<<HTML
<a id="{$data['attributes']['id']}" data-json={$encodedData} class='gie-single js-gettyEmbed' href="{$encodedURL}"> Embed from Getty Images</a>
HTML;
       return $result;
    }

    /**
     * Parses the oembed response html for embed attributes.
     *
     * @param string $html
     * @return array $data
     */
    private function parseResponseHtml(string $html): array {
        $data =[];

        preg_match(
            '/id:\'(?<id>[a-zA-Z0-9-_]+)\'/i', $html,
            $id
        );
        if ($id) {
            $data['id'] = $id['id'];
        }

        preg_match( '/sig:\'(?<sig>[a-zA-Z0-9-_]+=)\'/i', $html,$sig);
        if ($sig) {
            $data['sig'] = $sig['sig'];
        }

        preg_match( '/items:\'(?<item>[0-9-]+)\'/i', $html,$item);
        if ($item) {
            $data['items'] = $item['item'];
        }

        preg_match( '/caption: (?<isCaption>true|false)/i', $html,$isCaptioned);
        if ($isCaptioned) {
            $data['isCaptioned'] = $isCaptioned['isCaption'];
        }

        preg_match( '/is360: (?<is360>true|false)/i', $html,$is360);
        if ($is360) {
            $data['is360'] = $is360['is360'];
        }

        preg_match( '/tld:\'(?<tld>[a-zA-Z]+)\'/i', $html,$tld);
        if ($tld) {
            $data['tld'] = $tld['tld'];
        }
        return $data;
    }
}
