<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Formatting;

/**
 * Interface to provide headings.
 */
interface HeadingProviderInterface {

    /**
     * Fetch a list of headings from something.
     *
     * @return Heading[]
     */
    public function getHeadings(): array;
}
