<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Site\SiteSectionModel;
use Vanilla\Web\Asset\SiteAsset;

/**
 * Quote embed factory for comments.
 */
class DiscussionEmbedFactory extends AbstractOwnSiteEmbedFactory {

    /** @var \DiscussionsApiController */
    private $discussionApi;

    /**
     * DI
     *
     * @param RequestInterface $request
     * @param SiteSectionModel $siteSectionModel
     * @param \DiscussionsApiController $discussionApi
     */
    public function __construct(
        RequestInterface $request,
        SiteSectionModel $siteSectionModel,
        \DiscussionsApiController $discussionApi
    ) {
        parent::__construct($request, $siteSectionModel);
        $this->discussionApi = $discussionApi;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain = ''): string {
        $regexRoot = $this->getRegexRoot();
        return "/^$regexRoot\/discussion\/(?<discussionID>\d+)/i";
    }

    /**
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        $path = parse_url($url, PHP_URL_PATH);
        preg_match($this->getSupportedPathRegex(), $path, $matches);
        $id = $matches['discussionID'] ?? null;

        if ($id === null) {
            throw new NotFoundException('Discussion');
        }

        $discussion = $this->discussionApi->get_quote($id);
        $data = $discussion + [
                'embedType' => QuoteEmbed::TYPE,
            ];
        return new QuoteEmbed($data);
    }
}
