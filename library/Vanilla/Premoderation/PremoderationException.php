<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

use Garden\Web\Exception\ClientException;

/**
 * Exception thrown when a premoderation handler wants to block an item.
 */
class PremoderationException extends ClientException
{
    public function __construct(public PremoderationItem $item, public PremoderationResult $result, array $context = [])
    {
        $recordType = $item->recordType === "discussion" ? "post" : $item->recordType;
        $message = sprintf("Your %s will appear after it is approved.", strtolower($recordType));

        parent::__construct($message, 202, $context);
    }
}
