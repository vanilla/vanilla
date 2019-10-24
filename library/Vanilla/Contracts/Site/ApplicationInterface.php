<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

/**
 * Interface representing application.
 */
interface ApplicationInterface {
    /**
     * Get the application name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get the list of reserved slugs managed by this application only.
     *
     * @return string[]
     */
    public function getReservedSlugs(): array;
}
