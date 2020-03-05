<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Vanilla\Contracts\Models\UserProviderInterface;
use Vanilla\EmbeddedContent\AbstractEmbed;
use Vanilla\EmbeddedContent\EmbeddedContentException;
use Vanilla\EmbeddedContent\EmbedFilterInterface;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Quill\Parser;

/**
 * Class for filtering data inside of quote embeds.
 */
class QuoteEmbedFilter implements EmbedFilterInterface {

    /** @var FormatService */
    private $formatService;

    /** @var UserProviderInterface */
    private $userProvider;

    /**
     * DI.
     *
     * @param FormatService $formatService
     * @param UserProviderInterface $userProvider
     */
    public function __construct(FormatService $formatService, UserProviderInterface $userProvider) {
        $this->formatService = $formatService;
        $this->userProvider = $userProvider;
    }


    /**
     * @inheritdoc
     */
    public function canHandleEmbedType(string $embedType): bool {
        return $embedType === QuoteEmbed::TYPE;
    }

    /**
     * Filter embedded content
     *
     * @inheritdoc
     */
    public function filterEmbed(AbstractEmbed $embed): AbstractEmbed {
        if (!($embed instanceof QuoteEmbed)) {
            throw new EmbeddedContentException('Expected a quote embed. Instead got a ' . get_class($embed));
        }
        $this->replaceUser($embed);
        $this->cleanupBody($embed);
        return $embed;
    }

    /**
     * Replace the user data from the user model.
     * We have an trusted HTML field here "label".
     * Fetch an updated user record to:
     * - Ensure we have an accurate username.
     * - Ensure we have an accurate label.
     * - Make sure our label comes from a trusted source.
     *
     * @param QuoteEmbed $embed
     */
    private function replaceUser(QuoteEmbed $embed) {
        $verifiedUser = $this->userProvider->getFragmentByID($embed->getUserID(), true);
        $embed->updateData(['insertUser' => $verifiedUser], false);
    }

    /**
     * Cleanup the body of the quote.
     *
     * - Strip out nested embeds.
     * - Rerender it to ensure it's secure.
     *
     * @param QuoteEmbed $embed
     */
    private function cleanupBody(QuoteEmbed $embed) {
        $bodyRaw = $embed->getData()['bodyRaw'];
        $format = $embed->getData()['format'];

        if ($embed->getDisplayOptons()->isRenderFullContent()) {
            $renderedBody = $this->formatService->renderHTML($bodyRaw, $format);
        } else {
            if (strtolower($format) === RichFormat::FORMAT_KEY) {
                $bodyRaw = $this->stripNestedRichEmbeds($bodyRaw);
            }
            $renderedBody = $this->formatService->renderQuote($bodyRaw, $format);
        }

        $embed->updateData([
            'body' => $renderedBody,
            'bodyRaw' => $bodyRaw,
        ], false);
    }

    /**
     * Strip nested embeds from a raw rich body.
     *
     * @param string $richRawBody A rich content body, JSON encoded.
     *
     * @return string
     */
    private function stripNestedRichEmbeds(string $richRawBody): string {
        $arrayBody = Parser::jsonToOperations($richRawBody);
        // Iterate through the nested embed.
        foreach ($arrayBody as $subInsertIndex => &$subInsertOp) {
            $insert = &$subInsertOp['insert'];
            if (is_array($insert)) {
                $url = $insert['embed-external']['data']['url'] ?? null;
                if ($url !== null) {
                    // Replace the embed with just a link.
                    $linkEmbedOps = $this->makeLinkEmbedInserts($url);
                    array_splice($arrayBody, $subInsertIndex, 1, $linkEmbedOps);
                }
            }
        }

        return json_encode($arrayBody, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Make the contents of a link embed.
     *
     * @param string $url
     * @return array
     */
    private function makeLinkEmbedInserts(string $url): array {
        return [
            [
                'insert' => $url,
                'attributes' => [
                    'link' => $url,
                ],
            ],
            [ 'insert' => "\n" ],
        ];
    }
}
