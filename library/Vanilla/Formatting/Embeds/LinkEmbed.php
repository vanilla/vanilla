<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Embeds;

use Garden\TwigTrait;
use Gdn_Format;
use Vanilla\PageScraper;
use Vanilla\Web\TwigRenderTrait;

/**
 * Generic link embed.
 */
class LinkEmbed extends Embed {

    use TwigRenderTrait;

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

        return $this->renderTwig('library/Vanilla/Formatting/Embeds/LinkEmbed.twig', $data);

    }
}
