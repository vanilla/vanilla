<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cache;

/**
 * PSR-16 invalid argument exception.
 */
class InvalidArgumentException extends CacheException implements \Psr\SimpleCache\InvalidArgumentException {

}
