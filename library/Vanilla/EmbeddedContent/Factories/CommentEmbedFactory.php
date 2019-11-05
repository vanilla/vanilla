<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Factories;

use Garden\Web\Exception\NotFoundException;
use Garden\Web\RequestInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Site\SiteSectionModel;

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
     * @param SiteSectionModel $siteSectionModel
     * @param \CommentsApiController $commentApi
     */
    public function __construct(
        RequestInterface $request,
        SiteSectionModel $siteSectionModel,
        \CommentsApiController $commentApi
    ) {
        parent::__construct($request, $siteSectionModel);
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
        $path = parse_url($url, PHP_URL_PATH);
        preg_match($this->getSupportedPathRegex(), $path, $matches);
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
