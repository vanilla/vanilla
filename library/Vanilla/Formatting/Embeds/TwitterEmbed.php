<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

/**
 * Twitter embed.
 */
class TwitterEmbed extends Embed {

    /** @inheritdoc */
    protected $domains = ['twitter.com'];

    /**
     * TwitterEmbed constructor.
     */
    public function __construct() {
        parent::__construct('twitter', 'video');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $data = null;

        // Twitter doesn't give a lot with its oEmbed response and we don't need it for rendering Twitter cards.
        preg_match(
            '/https?:\/\/(?:www\.)?twitter\.com\/(?:#!\/)?(?:[^\/]+)\/status(?:es)?\/(?<statusID>[\d]+)/i',
            $url,
        $matches
        );
        if (array_key_exists('statusID', $matches)) {
            $data = $data ?: [];
            if (!array_key_exists('attributes', $data)) {
                $data['attributes'] = [];
            }
            $data['attributes']['statusID'] = $matches['statusID'];
        }

        return $data;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? [];
        $statusID = $attributes['statusID'] ?? '';
        $url = $data['url'] ?? '';

        $encodedStatusID = htmlspecialchars($statusID);
        $encodedUrl = \Gdn_Format::sanitizeUrl(htmlspecialchars($url));

        $result = <<<HTML
<div class="embedExternal embedTwitter">
    <div class="embedExternal-content js-twitterCard" data-tweeturl="{$encodedUrl}" data-tweetid="{$encodedStatusID}">
        <a href="{$encodedUrl}" class="tweet-url" rel="nofollow">{$encodedUrl}</a>
    </div>
</div>
HTML;
        return $result;
    }
}
