<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Formatting;

/**
 * Represents a collection of text fragments.
 */
interface TextFragmentCollectionInterface
{
    /**
     * Get the fragments from this instance.
     *
     * @return TextFragmentInterface[] Returns an array of text fragments.
     */
    public function getFragments(): array;
}
