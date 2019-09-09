<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface for providing all records that are attached to a site section.
 */
interface SiteSectionAttachmentProviderInterface {

    /**
     * Determine whether a group of sections can be deleted.
     *
     * @param array $sectionIDs The section IDs to test.
     * @return AttachedSectionRecordGroup[]
     */
    public function getForSectionIDs(array $sectionIDs): array;
}
