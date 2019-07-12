<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Formatting\Exception\FormattingException;

/**
 * Class for filtering Rich content before it gets inserted into the database.
 */
class Filterer {

    /**
     * Filter the contents of a quill post.
     *
     * - Validates content.
     * - Strips useless embed data.
     *
     * @param string $content The content to filter.
     * @return string
     *
     * @throws FormattingException If the JSON could not be converted to operations.
     */
    public function filter(string $content): string {
        $operations = Parser::jsonToOperations($content);
        // Re-encode the value to escape unicode values.
        $operations = $this->cleanupEmbeds($operations);
        $operations = json_encode($operations);
        return $operations;
    }


    /**
     * There is certain embed data from the rich editor that we want to strip out. This includes
     *
     * - Malformed partially formed operations (dataPromise).
     * - Nested embed data.
     *
     * @param array[] $operations The quill operations to loop through.
     * @return array
     */
    private function cleanupEmbeds(array &$operations): array {
        foreach ($operations as $key => &$op) {
            if (!is_array($op['insert']['embed-external'] ?? null)) {
                continue;
            }
            $embed = &$op['insert']['embed-external'];

            // If a dataPromise is still stored on the embed, that means it never loaded properly on the client.
            // We want to strip these embeds that haven't finished properly loading.
            $dataPromise = $embed['dataPromise'] ?? null;
            if ($dataPromise !== null) {
                unset($operations[$key]);
            }

            if (!is_array($embed['data'] ?? null)) {
                // We only care about embeds operations.
                continue;
            }
            $embedData = &$embed['data'];


            if (!$embedData) {
                // Clean up that messed up operation.
                $operations[$key];
                continue;
            }

            // Remove the rendered bodies. The raw bodies are the source of truth.
            $format = &$embedData['format'] ?? null;
            $bodyRaw = &$embedData['bodyRaw'] ?? null;
            $type = &$embedData['type'] ?? null;

            if ($type !== QuoteEmbed::TYPE) {
                // We only care about quote embeds specifically.
                continue;
            }

            // Remove nested external embed data. We don't want it rendered and this will prevent it from being
            // searched.
            if ($format === 'Rich' && is_array($bodyRaw)) {
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
            }

            // Finally render the new body to overwrite the previous HTML body.
            // We also need to ensure we've safely rendered the body to prevent innacurate content.
            $embedData['body'] = \Gdn_Format::quoteEmbed($bodyRaw, $format);
        }

        return array_values($operations);
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
