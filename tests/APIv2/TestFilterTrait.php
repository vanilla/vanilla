<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use VanillaTests\BootstrapTrait;

/**
 * Allows an `AbstractResourceTest` to test API filtering.
 */
trait TestFilterTrait {
    /**
     * @var array The fields that are allowed for sorting.
     */
    protected $filterFields = [];

    /**
     * Test whether each of the filter fields works on a resources index endpoint.
     */
    public function testFilter(): void {
        TestCase::assertNotEmpty($this->filterFields, "Specify some filter fields to test.");
        $row = $this->testPost();

        foreach ($this->filterFields as $key) {
            $query = [$key => $row[$key]];
            $rows = $this->api()->get($this->baseUrl, $query)->getBody();
            BootstrapTrait::assertArrayMatchesFilter($rows, $query);
        }
    }
}
