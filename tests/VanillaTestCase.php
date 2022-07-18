<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Fixtures\Request;

/**
 * A utility base class for PHPUnit tests.
 *
 * This class is meant to hold useful utility functions for tests. Any nice, generic assertion, constant, or helper
 * function should be put here so that it's easy to find.
 *
 * When adding helpers to this class try and make as many of the helpers public and static as possible to model the
 * base class assertions. This allows the methods to be used in traits and other subclasses more easily.
 */
class VanillaTestCase extends TestCase
{
    // Useful role names for many test fixtures.
    public const ROLE_ADMIN = "Administrator";
    public const ROLE_MOD = "Moderator";
    public const ROLE_MEMBER = "Member";

    /**
     * @var array An array of counters for applying to records.
     */
    protected static $counters = ["" => 0];

    /**
     * Call a closure on another object to access its private properties.
     *
     * FOR TESTING ONLY.
     *
     * @param object $on The object to bind the closure to.
     * @param \Closure $callable The closure to bind.
     * @param array $args The arguments to pass to the call.
     * @return mixed Returns the result of the call.
     */
    public static function callOn(object $on, \Closure $callable, ...$args)
    {
        $fn = $callable->bindTo($on, $on);
        return $fn(...$args);
    }

    /**
     * Call protected/private method of a class.
     *
     * Do not abuse this since it's not super fast!
     *
     * @param object|string $target Instantiated object or class that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws \ReflectionException Throws an exception if the `$target` isn't a real class.
     */
    public static function invokeMethod($target, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass($target);

        if (is_object($target)) {
            $instance = $target;
        } else {
            $instance = null;
        }

        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        if ($method->isStatic()) {
            return $method->invokeArgs(null, $parameters);
        } else {
            if (!isset($instance)) {
                throw new \Exception("Cannot call an instance method on a class.");
            }
            return $method->invokeArgs($target, $parameters);
        }
    }

    /**
     * Call `sprintf()` on each string array value while increasing the counter for the next call.
     *
     * This helper is useful for generating unique record values for repeatedly inserting test data with tests. Wrap
     * a static record value with calls and it will change the value each test.
     *
     * @param array $record The record to modify.
     * @param string $counter The name of the counter. You usually leave this blank unless you are super picky.
     * @return array Returns the new record.
     */
    public static function sprintfCounter(array $record, string $counter = ""): array
    {
        $count = self::id($counter);

        foreach ($record as &$value) {
            if (is_string($value) && !isUrl($value)) {
                $value = sprintf($value, $count);
            }
        }

        return $record;
    }

    /**
     * Generate an ever incrementing ID for tests.
     *
     * @param string $counter The name of the counter. You usually leave this blank unless you are super picky.
     * @return int Returns the new value of the counter.
     */
    public static function id(string $counter = ""): int
    {
        self::$counters += [$counter => 0];
        $count = ++self::$counters[$counter];

        return $count;
    }

    /**
     * Assert that a deep array is a subset of another deep array.
     *
     * @param array $subset The subset to test.
     * @param array $array The array to test against.
     * @param bool $strict Whether or not to use strict comparison.
     * @param string $message A message to display on the test.
     */
    public static function assertArraySubsetRecursive(array $subset, array $array, $strict = true, $message = ""): void
    {
        self::filterArraySubset($array, $subset);
        if ($strict) {
            self::assertSame($subset, $array, $message);
        } else {
            self::assertEquals($subset, $array, $message);
        }
    }

    /**
     * Filter a parent array so that it doesn't include any keys that the child doesn't have.
     *
     * This also sorts the arrays by key so they can be compared.
     *
     * @param array $parent The subset to filter.
     * @param array $subset The parent array.
     */
    private static function filterArraySubset(array &$parent, array &$subset): void
    {
        $parent = array_intersect_key($parent, $subset);

        ksort($parent);
        ksort($subset);

        foreach ($parent as $key => &$value) {
            if (is_array($value) && isset($subset[$key]) && is_array($subset[$key])) {
                // Recurse into the array.
                self::filterArraySubset($value, $subset[$key]);
            }
        }
    }

    /**
     * Ensure a dataset has a row matching a filter.
     *
     * @param array $rows The array to search.
     * @param array $filter The filter to pass to it.
     * @param string $message The error message.
     * @return mixed Returns the matching row.
     */
    public static function assertDatasetMatchesFilter(array $rows, array $filter, string $message = "")
    {
        TestCase::assertNotEmpty($rows, "Can't test a filter on an empty dataset.");
        $fn = ArrayUtils::filterCallback($filter, false);

        foreach ($rows as $row) {
            TestCase::assertTrue($fn($row), $message ?: "The row doesn't match the filter.");
        }
    }

    /**
     * Ensure a dataset has a row matching a filter.
     *
     * @param array|\Gdn_DataSet $rows The array to search.
     * @param array $filter The filter to pass to it.
     * @param string $message The error message.
     * @return mixed Returns the matching row.
     */
    public static function assertDatasetHasRow($rows, array $filter, string $message = "")
    {
        if ($rows instanceof \Gdn_DataSet) {
            $rows = $rows->resultArray();
        }
        $rows = array_filter($rows, ArrayUtils::filterCallback($filter, false));
        if (count($rows) === 1) {
            return array_pop($rows);
        } else {
            $message = $message ?: "The array did not contain exactly one row matching the filter.";
            $message .= " " . count($rows) . " found.";

            TestCase::fail($message);
        }
    }

    /**
     * Assert that there is a config with a specific value.
     *
     * @param string $configKey
     * @param mixed $expectedValue
     */
    public static function assertConfigValue(string $configKey, $expectedValue)
    {
        /** @var ConfigurationInterface $config */
        $config = \Gdn::getContainer()->get(ConfigurationInterface::class);
        TestCase::assertEquals(
            $expectedValue,
            $config->get($configKey, null),
            "Configuration values did not match for key: '$configKey'"
        );
    }

    /**
     * Assert that a dataset is properly sorted.
     *
     * @param array $arr The actual array to assert.
     * @param string $fields The fields the dataset should be sorted by.
     */
    public static function assertSorted(array $arr, string ...$fields): void
    {
        $sorted = $arr;
        usort($sorted, ArrayUtils::sortCallback(...$fields));

        $actual = $expected = [];
        for ($i = 0; $i < count($arr); $i++) {
            foreach ($fields as $field) {
                $j = trim($field, "-");
                $actual[$i][$j] = $arr[$i][$j];
                $expected[$i][$j] = $sorted[$i][$j];
            }
        }
        TestCase::assertSame($expected, $actual, "The two arrays are not sorted the same: " . implode(", ", $fields));
    }

    /**
     * Compare two URLs with only a subset of querystring parameters necessary.
     *
     * @param string|Request $expected
     * @param string|Request $actual
     * @param string $message
     */
    public function assertUrlSubset($expected, $actual, $message = ""): void
    {
        $re = Request::box($expected);
        $ra = Request::box($actual);

        static::assertSame($re->getDomainAndPath(), $ra->getDomainAndPath(), $message);
        static::assertArraySubsetRecursive($re->getQuery(), $ra->getQuery(), $message);
    }

    /**
     * Provide the contents of the files in a directory for generating test data.
     *
     * This method takes a base directory and an array of file suffixes (including extensions). The file suffixes are
     * used to group files together so that several files can be used as arguments for a single test.
     *
     * It is often much easier to put test data into files so that you can take advantage of syntax highlighting and code formatting.
     *
     * Example
     *
     * ```
     * $r = VanillaTestCase::provideFileTests(PATH_FIXTURES.'/foo', '-input.txt', '-expected.txt');
     * ```
     *
     * In this example the directory will provide data for a test method that takes two arguments. The first argument
     * is populated from files ending in "-input.txt" while the second argument is populated from files ending in "-expected.txt".
     *
     * Note: Files ending in .json will be decoded.
     *
     * @param string $dir The base directory of the test files.
     * @param string[] $suffixes The argument suffixes.
     * @return array Returns a data provider array keyed by the base name of each file without any suffixes.
     */
    public static function provideFileTests(string $dir, ...$suffixes): array
    {
        if (empty($suffixes)) {
            $suffixes = [""];
        }
        $whitelist = [];
        if (is_array($suffixes[count($suffixes) - 1])) {
            $whitelist = array_pop($suffixes);
        }

        $result = [];
        $defaults = array_fill(0, count($suffixes), "");
        foreach ($suffixes as $i => $suffix) {
            $files = glob("$dir/*$suffix");
            foreach ($files as $file) {
                $basename = basename($file);
                $prefix = substr($basename, 0, -strlen($suffix));
                if (!empty($whitelist) && !in_array($prefix, $whitelist)) {
                    continue;
                }

                $contents = file_get_contents($file);

                switch (pathinfo($file, PATHINFO_EXTENSION)) {
                    case "json":
                        $contents = json_decode($contents, true);
                        break;
                }

                $result[$prefix][$i] = $contents;
                $result[$prefix] += $defaults;
            }
        }

        return $result;
    }

    /**
     * Assert that rows in some arrays are like rows in another array.
     *
     * @param array $expectedFields The expected fields and their values.
     * Example:
     * [
     *     'recordID' => [4, 652, 2],
     *     'shouldNotExist' => null,
     *     'otherField' => ['test', 'test2', 'test3'],
     * ]
     * @param array $actualRows The actual rows to check.
     * @param bool $strictOrder Should the items be strictly ordered.
     * @param int|null $count The expected count of rows.
     */
    protected static function assertRowsLike(
        array $expectedFields,
        array $actualRows,
        bool $strictOrder = true,
        ?int $count = null
    ) {
        if (is_int($count)) {
            self::assertEquals($count, count($actualRows));
        }

        foreach ($expectedFields as $expectedField => $expectedValues) {
            if ($expectedValues === null) {
                foreach ($actualRows as $result) {
                    self::assertArrayNotHasKey($expectedField, $result);
                }
            } else {
                $actualValues = [];
                foreach ($actualRows as $row) {
                    $actualValues[] = ArrayUtils::getByPath($expectedField, $row, null);
                }
                if (!$strictOrder) {
                    sort($actualValues);
                    sort($expectedValues);
                }

                self::assertEquals(
                    $expectedValues,
                    $actualValues,
                    "Found wrong values for expected field: $expectedField"
                );
            }
        }
    }

    /**
     * Assert that one set of data is like another.
     *
     * @param array $expected Map of 'path.to.data' => 'expected value'
     * @param array{string, mixed} $data A mapping of dot notation keys to expected values.
     */
    public static function assertDataLike(array $expected, array $data): void
    {
        foreach ($expected as $key => $expectedValue) {
            $actualValue = ArrayUtils::getByPath($key, $data);
            Assert::assertEquals(
                $expectedValue,
                $actualValue,
                "Expect key '$key' to be equal to:\n" .
                    json_encode($expectedValue, JSON_PRETTY_PRINT) .
                    "\n Instead got:\n" .
                    json_encode($actualValue, JSON_PRETTY_PRINT)
            );
        }
    }
}
