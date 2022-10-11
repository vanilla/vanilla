<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface for provider childIDs related to a site section.
 */
interface SiteSectionChildIDProviderInterface
{
    /**
     * Get child ids for a site section.
     *
     * @param SiteSectionInterface $siteSection The site section.
     *
     * @return array The childIDs in the format of `[ 'someTypeOfIDs' => [1, 5, 3, 5, 6] ]`
     */
    public function getChildIDs(SiteSectionInterface $siteSection): array;
}
