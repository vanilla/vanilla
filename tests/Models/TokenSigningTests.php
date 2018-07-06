<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Models;

use VanillaTests\SharedBootstrapTestCase;
use AccessTokenModel;
use VanillaTests\SiteTestTrait;
use Vanilla\TokenSigningTrait;
use VanillaTests\Fixtures\TokenModel

/**
 * Test the {@link AccessTokenModel}.
 */
class AccessTokenModelTest extends SharedBootstrapTestCase {
    use SiteTestTrait;

    public function testVerifyToken () {
        $model = new TokenModel();

    }

    public function __construct() {

}
