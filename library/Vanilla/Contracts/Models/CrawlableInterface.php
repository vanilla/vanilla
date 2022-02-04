<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Models;

/**
 * Models implement this interface to signify they are crawlable.
 */
interface CrawlableInterface {
    /**
     * Gets the crawl information
     *
     * - url: The URL template to crawl.
     * - min: The minimum value to crawl. Usually the primary key.
     * - max: The maximum value to crawl. Usually the primary key.
     * - count: An approximate count of the crawl rows.
     * - parameter: The discussion to pass to the crawl URL.
     *
     * @return array
     */
    public function getCrawlInfo(): array;
}
