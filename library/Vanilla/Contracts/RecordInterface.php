<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts;

/**
 * Interface for a record.
 */
interface RecordInterface {
    /**
     * @return string
     */
    public function getRecordType(): string;

    /**
     * @return int
     */
    public function getRecordID(): int;
}
