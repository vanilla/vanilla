<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

trait FormatCompatTrait {

    /**
     * @return FormatCompatibilityService
     */
    protected function formatCompatService(): FormatCompatibilityService {
        return \Gdn::getContainer()->get(FormatCompatibilityService::class);
    }

    /**
     * Apply the format compatibility layer to the a particular field.
     *
     * @param mixed $row An array representing a database row.
     * @param string $field The field name.
     * @param string $formatField The name of the field holding the format.
     */
    protected function applyFormatCompatibility(&$row, string $field, string $formatField) {
        if (is_array($row)) {
            if (isset($row[$field]) && isset($row[$formatField])) {
                $row[$field] = $this->formatCompatService()->convert($row[$field], $row[$formatField]);
            }
        } elseif (is_object($row)) {
            if (isset($row->$field) && isset($row->$formatField)) {
                $row->$field = $this->formatCompatService()->convert($row->$field, $row->$formatField);
            }
        }
    }
}
