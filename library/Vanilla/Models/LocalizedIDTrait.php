<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

/**
 * Trait for generating localized IDs for records on a model.
 */
trait LocalizedIDTrait {

    /**
     * Generate a localized ID of for the record.
     *
     * @param int $recordID
     * @param string $locale
     *
     * @return string The localized ID.
     */
    public function getLocalizedID(int $recordID, string $locale): string {
        return $this->getTable() . '_' . $recordID . '_' . $locale;
    }
}
