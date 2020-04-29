<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Vanilla\Web\APIExpandMiddleware;

/**
 * A test version of the `SSOIDMiddleware` that mocks out some of the functionality.
 */
class TestAPIExpandMiddleware extends APIExpandMiddleware {
    /**
     * TestSSOIDMiddleware constructor.
     *
     * @param string $basePath
     */
    public function __construct(string $basePath) {
        $this->setBasePath($basePath);
    }

    /**
     * A test version of the method with a trivial implementation.
     *
     * @param int[] $userIDs
     * @return array
     */
    protected function joinSSOIDs(array $userIDs): array {
        $r = [];
        foreach ($userIDs as $userID) {
            $r[$userID] = "sso-$userID";
        }

        return $r;
    }
}
