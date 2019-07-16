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
use Vanilla\EmbeddedContent\AbstractEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Web\Asset\SiteAsset;

/**
 * Quote embed factory for comments.
 */
class CommentEmbedFactory extends AbstractEmbedFactory {

    /** @var RequestInterface */
    private $request;

    /** @var \CommentsApiController */
    private $commentApi;

    /**
     * DI
     *
     * @param RequestInterface $request
     * @param \CommentsApiController $commentApi
     */
    public function __construct(RequestInterface $request, \CommentsApiController $commentApi) {
        $this->request = $request;
        $this->commentApi = $commentApi;
    }

    /**
     * @return array
     */
    protected function getSupportedDomains(): array {
        return [
            $this->request->getHost(),
        ];
    }

    /**
     * @inheritdoc
     */
    protected function getSupportedPathRegex(string $domain = ''): string {
        // We need ot be sure to the proper web root here.
        $root = SiteAsset::joinWebPath($this->request->getRoot(), '/discussion/comment');
        $root = str_replace('/', '\/', $root);

        return "/$root\/(?<commentID>\d+)/i";
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
