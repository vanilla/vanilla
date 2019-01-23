<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Gdn_Format;
use Vanilla\PageScraper;

/**
 * Generic link embed.
 */
class LinkEmbed extends Embed {

    /** @var PageScraper */
    private $pageScraper;

    /**
     * LinkEmbed constructor.
     *
     * @param PageScraper $pageScraper
     */
    public function __construct(PageScraper $pageScraper) {
        $this->pageScraper = $pageScraper;
        parent::__construct('link', 'link');
    }

    /**
     * @inheritdoc
     */
    public function matchUrl(string $url) {
        $result = [
            'url' => $url,
            'name' => null,
            'body' => null,
            'photoUrl' => null,
            'media' => [],
            'attributes' => [],
        ];

        if ($this->isNetworkEnabled()) {
            $pageInfo = $this->pageScraper->pageInfo($url);
            $images = $pageInfo['Images'] ?? [];

            $result['name'] = $pageInfo['Title'] ?: null;
            $result['body'] = $pageInfo['Description'] ?: null;
            $result['photoUrl'] = !empty($images) ? reset($images) : null;
            $result['media'] = !empty($images) ? ['image' => $images] : [];
            $result['attributes'] = $pageInfo['Attributes'] ?? [];
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function renderData(array $data): string {
        $url = $data['url'] ?? null;
        $name = $data['name'] ?? null;
        $body = $data['body'] ?? null;
        $photoUrl = $data['photoUrl'] ?? null;
        $timestamp = $data['timestamp'] ?? null;
        $humanTime = $data['humanTime'] ?? null;

        if ($photoUrl) {
            $photoUrlEncoded = htmlspecialchars($photoUrl);
            $image = "<img src='$photoUrlEncoded' class='embedLink-image' aria-hidden='true'>";
        } else {
            $image = "";
        }

        if ($timestamp && $humanTime) {
            $timestampAsMeta = "<time class=\"embedLink-dateTime metaStyle\" dateTime=\"$timestamp\">$humanTime</time>";
        } else {
            $timestampAsMeta = "";
        }

        $urlEncoded = htmlspecialchars(\Gdn_Format::sanitizeUrl($url));
        $urlAsMeta = "<span class=\"embedLink-source metaStyle\">$urlEncoded</span>";
        $nameEncoded = htmlspecialchars($name);
        $bodyEncoded = htmlspecialchars($body);

        $result = <<<HTML
<div class="embedExternal embedText embedLink">
    <div class="embedExternal-content embedText-content embedLink-content">
        <a class="embedLink-link" href="{$urlEncoded}" rel="noopener noreferrer">
            <article class="embedText-body">
                {$image}
                <div class="embedText-main">
                    <div class="embedText-header">
                        <h3 class="embedText-title">{$nameEncoded}</h3>
                        {$timestampAsMeta}
                        {$urlAsMeta}
                    </div>
                    <div class="embedLink-excerpt">{$bodyEncoded}</div>
                </div>
            </article>
        </a>
    </div>
</div>
HTML;

        return $result;
    }
}
