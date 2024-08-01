<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

/**
 * Class for getting extra site meta information.
 */
abstract class SiteMetaExtra
{
    /**
     * @return array
     */
    abstract public function getValue(): array;
}
