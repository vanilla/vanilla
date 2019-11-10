<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use VanillaTests\InternalClient;

trait TestPutFieldTrait {
    /**
     * Test updating a field with PUT.
     *
     * @param string $action
     * @param mixed $val
     * @param string|null $col
     * @throws \Exception if the new record already has its field set to the target value.
     * @dataProvider providePutFields
     */
    public function testPutField($action, $val, $col = null) {
        if ($col === null) {
            $col = $action;
        }
        $row = $this->testPost();

        $before = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");
        if ($before[$col] === $val) {
            $printVal = var_export($val, true);
            throw new \Exception("Unable to test PUT for {$this->singular} field: {$col} is already {$printVal}");
        }
        $urlAction = urlencode($action);
        $this->api()->put("{$this->baseUrl}/{$row[$this->pk]}/{$urlAction}", [$col => $val]);
        $after = $this->api()->get("{$this->baseUrl}/{$row[$this->pk]}");

        $this->assertEquals($val, $after[$col]);
    }

    abstract function providePutFields();
    abstract function testPost($record = null, array $extra = []);

    /**
     * Get the API client for internal requests.
     *
     * @return InternalClient Returns the API client.
     */
    abstract function api();

    /**
     * {@inheritDoc}
     */
    abstract public function assertEquals($expected, $actual, string $message = '', float $delta = 0.0, int $maxDepth = 10, bool $canonicalize = false, bool $ignoreCase = false);
}
