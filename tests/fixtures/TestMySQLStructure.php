<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

/**
 * A test version of `Gdn_MySQLStructure` for inspecting some protected methods.
 */
class TestMySQLStructure extends \Gdn_MySQLStructure {
    /**
     * Exposes the `Gdn_MySQLStructure::getCreateTable()` method.
     *
     * @return string
     */
    public function dumpCreateTable(): string {
        return $this->getCreateTable();
    }
}
