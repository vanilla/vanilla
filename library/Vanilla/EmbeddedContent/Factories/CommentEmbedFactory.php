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
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;

/**
 * Quote embed factory for comments.
 */
final class CommentEmbedFactory extends AbstractOwnSiteEmbedFactory {

    /** @var \CommentsApiController */
    private $commentApi;

    /**
     * DI
     *
     * @param RequestInterface $request
     * @param SiteSectionProviderInterface $siteSectionProvider
     * @param \CommentsApiController $commentApi
     */
    public function __construct(
        RequestInterface $request,
        SiteSectionProviderInterface $siteSectionProvider,
        \CommentsApiController $commentApi
    ) {
        parent::__construct($request, $siteSectionProvider);
        $this->commentApi = $commentApi;
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain = ''): string {
        $regexRoot = $this->getRegexRoot();
        return "/^$regexRoot\/discussion\/comment\/(?<commentID>\d+)/i";
    }

    /**
     * @inheritdoc
     */
    public function createEmbedForUrl(string $url): AbstractEmbed {
        preg_match($this->getSupportedPathRegex(), $url, $matches);
        $id = $matches['commentID'] ?? null;

        if ($id === null) {
            throw new NotFoundException('Comment');
        }

        $comment = $this->commentApi->get_quote($id);
        $data = $comment + [
            'embedType' => QuoteEmbed::TYPE,
        ];
        return new QuoteEmbed($data);
    }
}
