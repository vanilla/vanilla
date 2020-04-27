<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\FileRehoster;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for the File Rehoster.
 */
class FileRehosterTest extends SharedBootstrapTestCase {

    public function testSafeUrlResolver() {
        /** @var FileRehoster $rehoster */
        $rehoster = self::container()->get(FileRehoster::class);
//        $response = $rehoster->copyRemoteFileLocal('http://vanillaforums.com');
        $rehoster->copyRemoteFileLocal('https://vanillaforums.com/images/productShots/home_laptop.png');
    }
}
