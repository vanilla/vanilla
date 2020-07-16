<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

use Garden\Web\Exception\ServerException;

/**
 * Exception to through if more resource events than allowed are being processed at once.
 */
class ResourceEventLimitException extends ServerException {

    /**
     * Constructor.
     *
     * @param int $limit The set limit.
     * @param int $triedProcessing The amount of records that were attempted to be processed.
     * @param array $context Exception context.
     */
    public function __construct(int $limit, int $triedProcessing, array $context = []) {
        $message = "Attempted to process $triedProcessing events. The limit is $limit.";
        parent::__construct($message, 500, $context);
    }
}
