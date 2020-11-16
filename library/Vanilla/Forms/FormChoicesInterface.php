<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

/**
 * Interface FormChoicesInterface
 *
 * @package Vanilla\Forms
 */
interface FormChoicesInterface {

    /**
     * Get all choices.
     *
     * @return array
     */
    public function getChoices(): array;
}
