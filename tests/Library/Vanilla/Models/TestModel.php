<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Models;

use Vanilla\Models\Model;

/**
 * A basic model for testing the factory or whatever.
 */
class TestModel extends Model {
    /**
     * TestModel constructor.
     */
    public function __construct() {
        parent::__construct('testModel');
    }
}
