<?php
/**
 * @author Dani M <dani.m@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Formatting;

/**
 * Interface FormatFieldInterface
 *
 * @package Vanilla\Contracts\Formatting
 */
interface FormatFieldInterface {

    /**
     * Enable/disable field formatting.
     *
     * @param bool $doFieldFormatting Enable/disable formatting.
     */
    public function enableFieldFormatting(bool $doFieldFormatting);

    /**
     * Format a specific field.
     *
     * @param array $row An array representing a database row.
     * @param string $field The field name.
     * @param string $format The source format.
     */
    public function formatField(array &$row, $field, $format);
}
