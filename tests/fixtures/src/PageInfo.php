<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Fixtures;

/**
 * A PageInfo class, limited to local files.
 */
class PageInfo extends \Vanilla\PageInfo {
    /** @inheritDoc */
    protected $validSchemes = ['file'];
}
