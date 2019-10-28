<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\EmbedFilterInterface;
use Vanilla\Formatting\Formats\RichFormat;

/**
 * Class for filtering data inside of quote embeds.
 */
class QuoteEmbedFilter implements EmbedFilterInterface {

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
    public function filterData(array $embedData): array {
        $bodyRaw = &$embedData['bodyRaw'] ?? null;
        $format = &$embedData['format'] ?? null;

        $stringBodyRaw = $bodyRaw;

        // Remove nested external embed data. We don't want it rendered and this will prevent it from being
        // searched.
        if (strtolower($format) === RichFormat::FORMAT_KEY && is_array($bodyRaw)) {
            // Iterate through the nested embed.
            foreach ($bodyRaw as $subInsertIndex => &$subInsertOp) {
                $insert = &$subInsertOp['insert'];
                if (is_array($insert)) {
                    $url = $insert['embed-external']['data']['url'] ?? null;
                    if ($url !== null) {
                        // Replace the embed with just a link.
                        $linkEmbedOps = $this->makeLinkEmbedInserts($url);
                        array_splice($bodyRaw, $subInsertIndex, 1, $linkEmbedOps);
                    }
                }
            }
            $stringBodyRaw = json_encode($bodyRaw, JSON_UNESCAPED_UNICODE);
        }

        // Fix improperly encoded unicode:
        if (strstr($stringBodyRaw, "\\u") !== false) {
            $decoded = json_decode($stringBodyRaw);
            $stringBodyRaw = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            $embedData['bodyRaw'] = $stringBodyRaw;
        }

        // Finally render the new body to overwrite the previous HTML body.
        if ($embedData['displayOptions']['renderFullContent'] ?? null) {
            $embedData['body'] = \Gdn::formatService()->renderHTML($stringBodyRaw, $format);
        } else {
            $embedData['body'] = \Gdn::formatService()->renderQuote($stringBodyRaw, $format);
        }

        return $embedData;
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
