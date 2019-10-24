<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Formats\RichFormat;

/**
 * Class for filtering Rich content before it gets inserted into the database.
 */
class Filterer {

//    /** @var EmbedService */
//    private $embedService;
//
//    /**
//     * DI.
//     *
//     * @param EmbedService $embedService
//     */
//    public function __construct(EmbedService $embedService) {
//        $this->embedService = $embedService;
//    }


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
        $operations = json_encode($operations, JSON_UNESCAPED_UNICODE);
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
                // Strip off any malformed embeds.
                unset($operations[$key]);
                continue;
            }
            $embedData = &$embed['data'];
            $embedService = \Gdn::getContainer()->get(EmbedService::class);
            $embed['data'] = $embedService->filterEmbedData($embedData);
        }

        return array_values($operations);
    }
}
