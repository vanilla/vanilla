<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forms;

class FormModalOptions
{
    public function __construct(public string $title, public string $submitLabel)
    {
    }
}
