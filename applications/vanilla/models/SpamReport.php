<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

class SpamReport
{
    public function __construct(
        public string $recordType,
        public ?string $insertUserName,
        public ?string $insertUserEmail,
        public string $bodyPlainText,
        public ?string $insertIPAddress,
        public ?string $url
    ) {
    }
}
