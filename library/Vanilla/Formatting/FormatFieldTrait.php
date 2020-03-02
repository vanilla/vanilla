<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Add basic field formatting with fallback render result.
 */
trait FormatFieldTrait {

    /** @var bool */
    private $doFieldFormatting = true;

    /**
     * Format a specific field.
     *
     * @param array $row An array representing a database row.
     * @param string $field The field name.
     * @param string $format The source format.
     */
    public function formatField(array &$row, $field, $format) {
        if ($this->doFieldFormatting && array_key_exists($field, $row)) {
            $row[$field] = \Gdn::formatService()->renderHTML($row[$field], $format) ?: '<!-- empty -->';
        }
    }

    /**
     * Enable/disable field formatting.
     *
     * @param bool $doFieldFormatting Enable/disable field formatting.
     */
    public function enableFieldFormatting(bool $doFieldFormatting) {
        $this->doFieldFormatting = $doFieldFormatting;
    }
}
