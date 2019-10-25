<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Provider for site sections.
 *
 * This is called a "provider" because it does not contain any methods for creating/modifying sections.
 * Some implementations may contain this behaviour but it is not strictly defined for this interface.
 */
interface SiteSectionProviderInterface {
    /**
     * Returns all sections of the site.
     *
     * @return SiteSectionInterface[]
     */
    public function getAll(): array;

    /**
     * Get a site section by it's ID.
     *
     * @param int $id The ID of the site section.
     *
     * @return SiteSectionInterface|null
     */
    public function getByID(int $id): ?SiteSectionInterface;

    /**
     * Get a site section from it's base path.
     *
     * @param string $basePath
     * @return SiteSectionInterface|null
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface;

    /**
     * Get all site sections that match a particular locale.
     *
     * @param string $localeKey The locale key to lookup by.
     * @return SiteSectionInterface[]
     */
    public function getForLocale(string $localeKey): array;

    /**
     * Get all site sections that match a particular site section group.
     *
     * @param string $sectionGroupKey The name of the section group to check.
     * @return SiteSectionInterface[]
     */
    public function getForSectionGroup(string $sectionGroupKey): array;

    /**
     * Get the current site section for the request automatically if possible.
     *
     * @return SiteSectionInterface
     */
    public function getCurrentSiteSection(): SiteSectionInterface;
}
