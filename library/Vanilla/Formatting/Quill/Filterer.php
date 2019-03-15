<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting\Quill;

use Vanilla\Formatting\Exception\FormattingException;

/**
 * Undocumented class
 */
class Filterer {
    /**
     * Filter the contents of a quill post.
     *
     * - Validates content.
     * - Strips useless embed data.
     *
     * @param string $content The content to filter.
     *
     * @throws FormattingException If the JSON could not be converted to operations.
     */
    public function filter(string $content): string {
        $operations = Parser::jsonToOperations($content);
        // Re-encode the value to escape unicode values.
        $operations = $this->stripUselessEmbedData($operations);
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
     */
    private function stripUselessEmbedData(array &$operations): array {
        foreach ($operations as $key => $op) {
            // If a dataPromise is still stored on the embed, that means it never loaded properly on the client.
            $dataPromise = $op['insert']['embed-external']['dataPromise'] ?? null;
            if ($dataPromise !== null) {
                unset($operations[$key]);
            }

            // Remove nested external embed data. We don't want it rendered and this will prevent it from being
            // searched.
            $format = $op['insert']['embed-external']['data']['format'] ?? null;
            if ($format === 'Rich') {
                $bodyRaw = &$op['insert']['embed-external']['data']['bodyRaw'] ?? null;
                if (is_array($bodyRaw)) {
                    foreach ($bodyRaw as $subInsertIndex => &$subInsertOp) {
                        $externalEmbed = &$bodyRaw[$subInsertIndex]['insert']['embed-external'] ?? null;
                        if ($externalEmbed !== null) {
                            unset($externalEmbed['data']);
                        }
                    }
                }
            }
        }

        return array_values($operations);
    }
}
