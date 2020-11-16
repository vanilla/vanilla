<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Interface FormFieldMatchInterface
 *
 * @package Vanilla\Forms
 */
interface FormFieldMatchInterface {

    /**
     * Get all conditions.
     *
     * @return array
     */
    public function getConditions(): array;
}
