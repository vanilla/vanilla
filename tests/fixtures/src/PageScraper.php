<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

/**
 * A PageScraper class, limited to local files.
 */
class PageScraper extends \Vanilla\PageScraper {
    /** @inheritDoc */
    protected $validSchemes = ['file'];
}
