<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\Site\AttachedSectionRecordGroup;
use Vanilla\Contracts\Site\SiteSectionAttachmentProviderInterface;

/**
 * Model for getting all records attached to a site section.
 *
 * This can be useful for performing bulk operations, or checking if it's possible
 * modify/delete a section.
 */
class SiteSectionAttachmentModel implements SiteSectionAttachmentProviderInterface {

    /** @var SiteSectionAttachmentProviderInterface */
    private $providers = [];

    /**
     * Register a provider for the model.
     *
     * @param SiteSectionAttachmentProviderInterface $provider
     */
    public function registerProvider(SiteSectionAttachmentProviderInterface $provider) {
        $this->providers[] = $provider;
    }

    /**
     * Return all record groups assosciated with a particular site section.
     *
     * @param array $sectionIDs
     * @return AttachedSectionRecordGroup[]
     */
    public function getForSectionIDs(array $sectionIDs): array {
        $recordGroups = [];
        /** @var SiteSectionAttachmentProviderInterface $provider */
        foreach ($this->providers as $provider) {
            $recordGroups = array_merge($recordGroups, $provider->getForSectionIDs($sectionIDs));
        }

        return $recordGroups;
    }
}
