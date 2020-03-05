<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Provider for applications.
 */
interface ApplicationProviderInterface {
    /**
     * Returns all applications of the site.
     *
     * @return ApplicationInterface[]
     */
    public function getAll(): array;

    /**
     * Returns all application reserved slugs.
     *
     * @return string[]
     */
    public function getReservedSlugs(): array;

    /**
     * Register an application.
     *
     * @param ApplicationInterface $application
     * @return $this
     */
    public function add(ApplicationInterface $application): self;
}
