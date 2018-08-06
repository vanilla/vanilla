<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0
 */

namespace Vanilla\Formatting\Embeds;

use Garden\Web\Exception\NotFoundException;

/**
 * Generic link embed.
 */
class QuoteEmbed extends Embed {

    protected $domains;

    /** @var \DiscussionsApiController */
    private $discussionsApiController;

    /** @var \CommentsApiController */
    private $commentsApiController;

    public function __construct(\DiscussionsApiController $discussionsApiController,
        \CommentsApiController $commentsApiController) {
        parent::__construct('quote', 'link');
        $this->domains = [parse_url(\Gdn::request()->domain())['host']];
        $this->discussionsApiController = $discussionsApiController;
        $this->commentsApiController = $commentsApiController;
    }

    /**
     * @param string $url
     * @return array|boolean
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\PermissionException
     */
    function matchUrl(string $url) {
        $path = parse_url($url)['path'];
        $path = str_replace("/".\Gdn::request()->webRoot(), "", $path);

        $idRegex = '/(^\/discussion\/(?<discussionID>\d+)|(\/discussion\/comment\/(?<commentID>\d+)))/i';
        preg_match($idRegex, $path, $matches);

        $commentID = $matches['commentID'] ? (int) $matches['commentID'] : null;
        $discussionID = $matches['discussionID'] ? (int) $matches['discussionID'] : null;

        try {
            if ($commentID !== null) {
                $data = $this->commentsApiController->get_quote($commentID);
                return [
                    "url" => $url,
                    "type" => "quote",
                    "attributes" => $data,
                ];
            } else if ($discussionID !== null) {
                $data = $this->discussionsApiController->get_quote($discussionID);
                return [
                    "url" => $url,
                    "type" => "quote",
                    "attributes" => $data,
                ];
            }
        } catch (NotFoundException $e) {
            return false;
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $attributes = $data['attributes'] ?? null;
        $url = $attributes['url'] ?? null;
        $bodyRaw = $attributes['bodyRaw'] ?? null;
        $format = $attributes['format'] ?? null;
        $sanitizedUrl = htmlspecialchars(\Gdn_Format::sanitizeUrl($url));
        $attributes['body'] = \Gdn_Format::quoteEmbed($bodyRaw, $format);
        unset($data['attributes']['bodyRaw']);
        $jsonData = json_encode($data);
        $result = <<<HTML
<div class="embedExternal embedText embedQuote">
    <div class="embedExternal-content">
        <div class="js-quoteEmbed" data-json='$jsonData'><a href="$sanitizedUrl">$sanitizedUrl</a></div>
    </div>
</div>
HTML;

        return $result;
    }
}
