<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

/**
 * Allows a test case to expect an error to occur.
 */
trait ExpectErrorTrait {
    /**
     * Run code that expects an error.
     *
     * This method is intended to test functions that have non-fatal errors. By using `expectError` you can assert that
     * test has an error, and at the same time test its return type.
     *
     * @param callable $test The test to run.
     * @param callable $onError An optional event handler to inspect the error thrown.
     */
    protected function expectError(callable $test, ?callable $onError = null): void {
        try {
            $hasError = false;

            set_error_handler(function ($errorNumber, $message, $file, $line, $arguments) use ($onError, &$hasError) {
                $ex = new \ErrorException($message, $errorNumber, $errorNumber, $file, $line);

                $hasError = true;
                if (is_callable($onError)) {
                    $onError($ex);
                }
            });
            $test();
            if (!$hasError) {
                $this->fail("An expected error never occurred.");
            }
        } finally {
            restore_error_handler();
        }
    }

    /**
     * A higher order function that asserts an error is a specific number.
     *
     * @param int $number One of the error constants.
     * @return callable Returns a callback that can be passed to higher order functions.
     */
    static function assertErrorNumber(int $number): callable {
        return function(\Throwable $ex) use ($number) {
            if ($number !== $ex->getCode()) {
                $this->fail("Failed asserting the error number: $number.");
            }
        };
    }
}
