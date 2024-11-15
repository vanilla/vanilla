<?php
/**
 * Forked from https://github.com/indentno/phpunit-pretty-print/blob/develop/LICENSE.md
 * @licence MIT
 */

namespace VanillaTests;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Runner\BaseTestRunner;
use PHPUnit\Util\Filter;

use PHPUnit\TextUI\DefaultResultPrinter;

/**
 * PHPUnit printer that outputs individual classnames, the time they took and the
 */
class PhpUnitProgressPrinter extends DefaultResultPrinter
{
    protected string $className;
    protected string|null $previousClassName = null;

    /**
     * @inheritDoc
     */
    public function startTest(Test $test): void
    {
        $this->className = get_class($test);
    }

    /**
     * @inheritDoc
     */
    public function endTest(Test $test, float $time): void
    {
        parent::endTest($test, $time);

        $testMethodName = \PHPUnit\Util\Test::describe($test);

        $parts = preg_split("/ with data set /", $testMethodName[1]);
        $methodName = array_shift($parts);
        $dataSet = array_shift($parts);

        // Convert capitalized words to lowercase
        $methodName = preg_replace_callback(
            "/([A-Z]{2,})/",
            function ($matches) {
                return strtolower($matches[0]);
            },
            $methodName
        );

        $name = lcfirst($methodName) . "()";

        // Get the data set name
        if ($dataSet) {
            // Note: Use preg_replace() instead of trim() because the dataset may end with a quote
            // (double quotes) and trim() would remove both from the end. This matches only a single
            // quote from the beginning and end of the dataset that was added by PHPUnit itself.
            $name .= " [ " . preg_replace('/^"|"$/', "", $dataSet) . " ]";
        }

        $status = $test instanceof TestCase ? $test->getStatus() : BaseTestRunner::STATUS_UNKNOWN;
        $this->write(" ");

        switch ($status) {
            case BaseTestRunner::STATUS_PASSED:
                $this->writeWithColor("fg-green", $name, false);

                break;
            case BaseTestRunner::STATUS_WARNING:
            case BaseTestRunner::STATUS_SKIPPED:
                $this->writeWithColor("fg-yellow", $name, false);

                break;
            case BaseTestRunner::STATUS_INCOMPLETE:
                $this->writeWithColor("fg-blue", $name, false);

                break;
            case BaseTestRunner::STATUS_ERROR:
            case BaseTestRunner::STATUS_FAILURE:
                $this->writeWithColor("fg-red", $name, false);

                break;
            case BaseTestRunner::STATUS_RISKY:
                $this->writeWithColor("fg-magenta", $name, false);

                break;
            case BaseTestRunner::STATUS_UNKNOWN:
            default:
                $this->writeWithColor("fg-cyan", $name, false);

                break;
        }

        $this->write(" ");

        $timeColor = $time > 0.5 ? "fg-yellow" : "fg-white";
        $this->writeWithColor($timeColor, "[" . number_format($time, 3) . "s]", lf: false);
        $memoryUsage = round(memory_get_usage(true) / 1024 / 1024, 2);

        $this->writeWithColor("fg-white", "[ {$memoryUsage}MB ]", lf: true);
    }

    /**
     * @inheritDoc
     */
    protected function writeProgress(string $progress): void
    {
        if ($this->previousClassName !== $this->className) {
            $this->write("\n");
            $this->writeWithColor("bold", $this->className, false);
            $this->writeNewLine();
        }

        $this->previousClassName = $this->className;

        $this->printProgress();

        switch (strtoupper(preg_replace('#\\x1b[[][^A-Za-z]*[A-Za-z]#', "", $progress))) {
            case ".":
                $this->writeWithColor("fg-green", "  ✓", false);

                break;
            case "S":
                $this->writeWithColor("fg-yellow", "  →", false);

                break;
            case "I":
                $this->writeWithColor("fg-blue", "  ∅", false);

                break;
            case "F":
                $this->writeWithColor("fg-red", "  x", false);

                break;
            case "E":
                $this->writeWithColor("fg-red", "  ⚈", false);

                break;
            case "R":
                $this->writeWithColor("fg-magenta", "  ⌽", false);

                break;
            case "W":
                $this->writeWithColor("fg-yellow", "  ¤", false);

                break;
            default:
                $this->writeWithColor("fg-cyan", "  ≈", false);

                break;
        }
    }

    /**
     * @param TestFailure $defect
     * @return void
     */
    protected function printDefectTrace(TestFailure $defect): void
    {
        $this->write($this->formatExceptionMsg($defect->getExceptionAsString()));
        $trace = Filter::getFilteredStacktrace($defect->thrownException());
        if (!empty($trace)) {
            $this->write("\n" . $trace);
        }
        $exception = $defect->thrownException()->getPrevious();
        while ($exception) {
            $this->write(
                "\nCaused by\n" .
                    TestFailure::exceptionToString($exception) .
                    "\n" .
                    Filter::getFilteredStacktrace($exception)
            );
            $exception = $exception->getPrevious();
        }
    }

    /**
     * @param $exceptionMessage
     * @return string
     */
    protected function formatExceptionMsg($exceptionMessage): string
    {
        $exceptionMessage = str_replace("+++ Actual\n", "", $exceptionMessage);
        $exceptionMessage = str_replace("--- Expected\n", "", $exceptionMessage);
        $exceptionMessage = str_replace("@@ @@", "", $exceptionMessage);

        if ($this->colors) {
            $exceptionMessage = preg_replace('/^(Exception.*)$/m', "\033[01;31m$1\033[0m", $exceptionMessage);
            $exceptionMessage = preg_replace('/(Failed.*)$/m', "\033[01;31m$1\033[0m", $exceptionMessage);
            $exceptionMessage = preg_replace("/(\-+.*)$/m", "\033[01;32m$1\033[0m", $exceptionMessage);
            $exceptionMessage = preg_replace("/(\++.*)$/m", "\033[01;31m$1\033[0m", $exceptionMessage);
        }

        return $exceptionMessage;
    }

    /**
     * @return void
     */
    private function printProgress()
    {
        $this->numTestsRun++;

        $total = $this->numTests;
        $current = str_pad($this->numTestsRun, strlen($total), "0", STR_PAD_LEFT);

        $this->write("[{$current}/{$total}]");
    }
}
