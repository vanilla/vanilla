<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Garden\Web\RequestInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbeddedContentException;
use Vanilla\EmbeddedContent\EmbedFilterInterface;

/**
 * Class for filtering data inside of twitch embeds.
 */
class TwitchEmbedFilter implements EmbedFilterInterface {

    /** @var string */
    private $host;

    /**
     * DI.
     *
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request) {
        $this->host = $request->getHost();
    }


    /**
     * @inheritdoc
     */
    public function canHandleEmbedType(string $embedType): bool {
        return $embedType === TwitchEmbed::TYPE;
    }

    /**
     * Filter embedded content
     *
     * @inheritdoc
     */
    public function filterEmbed(AbstractEmbed $embed): AbstractEmbed {
        if (!($embed instanceof TwitchEmbed)) {
            throw new EmbeddedContentException('Expected a twitch embed. Instead got a ' . get_class($embed));
        }

        $embed->setHost($this->host);
        return $embed;
    }
}
