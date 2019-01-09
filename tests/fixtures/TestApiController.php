<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

/**
 * A fixture for testing some of the basic controller functionality.
 */
class TestApiController extends \AbstractApiController {
    /**
     * {@inheritdoc}
     */
    public function resolveExpandFieldsPublic(array $request, array $map, $field = 'expand') {
        return $this->resolveExpandFields($request, $map, $field);
    }
}
