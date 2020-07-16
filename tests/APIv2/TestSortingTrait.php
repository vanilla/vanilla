<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv2;

use PHPUnit\Framework\TestCase;
use Vanilla\Utility\ArrayUtils;

/**
 * Allows an `AbstractResourceTest` to test API sorting.
 */
trait TestSortingTrait {
    /**
     * @var array The fields that are allowed for sorting.
     */
    protected $sortFields = [];

    /**
     * Get the URL used for test sorts.
     *
     * @return string
     */
    protected function sortUrl(): string {
        return $this->indexUrl();
    }

    /**
     * Test the sorting of a single field.
     *
     * @param string $field
     * @dataProvider provideSortFields
     */
    public function testIndexSort(string $field): void {
        $rows = $this->generateIndexRows();

        $fields = [$field, '-'.$field];

        foreach ($fields as $field) {
            /* @var AbstractResourceTest $this */
            $actual = $this->api()->get($this->sortUrl(), ['sort' => $field, 'pinOrder' => 'mixed'])->getBody();
            static::assertSorted($actual, $field);
        }
    }

    /**
     * Assert that a dataset is properly sorted.
     *
     * @param array $arr The actual array to assert.
     * @param string $fields The fields the dataset should be sorted by.
     */
    public static function assertSorted(array $arr, string ...$fields): void {
        $sorted = $arr;
        usort($sorted, ArrayUtils::sortCallback(...$fields));

        $actual = $expected = [];
        for ($i = 0; $i < count($arr); $i++) {
            foreach ($fields as $field) {
                $j = trim($field, '-');
                $actual[$i][$j] = $arr[$i][$j];
                $expected[$i][$j] = $sorted[$i][$j];
            }
        }
        TestCase::assertSame($expected, $actual, "The two arrays are not sorted the same: ".implode(', ', $fields));
    }

    /**
     * Get the sort fields as a data provider.
     *
     * @return string[]
     */
    public function provideSortFields(): array {
        $r = [];
        foreach ($this->sortFields as $field) {
            $r[$field] = [$field];
        }
        return $r;
    }
}
