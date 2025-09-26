<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\Utility\ArrayUtils;

trait NormalizeRowsTrait
{
    /**
     * Inner implementation of normalize rows. Things are normalized into a and array of rows.
     *
     * @param array $rows
     * @return array
     */
    abstract protected function normalizeRowsImpl(array &$rows): void;

    /**
     * @param array<array>|array $rowOrRows A single row or an array of rows.
     * @return array
     */
    public function normalizeRows(array &$rowOrRows): void
    {
        if (ArrayUtils::isAssociative($rowOrRows)) {
            $rows = [&$rowOrRows];
        } else {
            $rows = &$rowOrRows;
        }

        $this->normalizeRowsImpl($rows);
    }
}
