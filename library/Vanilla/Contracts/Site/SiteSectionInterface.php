<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface representing a section of a site.
 *
 * Through this mechanism content across the site may be separated and filtered.
 */
interface SiteSectionInterface extends \JsonSerializable {
    const APP_FORUM = 'forum';
    const APP_KB = 'knowledgeBase';
    /**
     * Get the base path for the section of the site.
     *
     * This should be some type of path starting with "/".
     * All web content in the section should then have it's URL prefixed with this section.
     *
     * @return string
     */
    public function getBasePath(): string;

    /**
     * Get the locale key to use for the site sections content.
     *
     * Some scenarios may lead to the user's UI locale being different from the content locale.
     * A section may have content 1 language generally, but a user may elect to have their UI in different dialect of the same language.
     *
     * @return string
     */
    public function getContentLocale(): string;

    /**
     * Get the display name for the section. This would common be used for titles and navigation.
     *
     * @return string
     */
    public function getSectionName(): string;

    /**
     * Get the uniqueID representing the section.
     *
     * @return string
     */
    public function getSectionID(): string;

    /**
     * Get the section group that a section belongs.
     *
     * @return string
     */
    public function getSectionGroup(): string;

    /**
     * Get default root controller route
     *
     * @return array
     */
    public function getDefaultRoute(): array;

    /**
     * Get enabled applications
     *
     * @return array
     */
    public function applications(): array;

    /**
     *  Check if application is enabled for site section.
     *
     * @param string $app
     * @return bool
     */
    public function applicationEnabled(string $app): bool;

    /**
     * Set application enabled or disabled.
     *
     * @param string $app
     * @param bool $enable
     * @return array
     */
    public function setApplication(string $app, bool $enable);

    /**
     * Get attributes assosciated with the site section.
     *
     * @return array
     */
    public function getAttributes(): array;
}
