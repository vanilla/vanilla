<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

/**
 * Command to split up php-unit tests.
 *
 * Usage: vnla split-tests --include-regex="/OnlyMatchThese/" --exclude-regex="/Don'tMatchThese/" --output-regex --parallelism=10 --offset=0
 */
class SplitTestsCommand
{
    private ?int $parallelism = null;

    private ?int $offset = null;

    private ?string $excludeRegex = null;

    private ?string $includeRegex = null;

    private bool $outputRegex = false;

    public function splitTests()
    {
        $extraArgs = $this->phpUnitArgs ?? "";
        exec(PATH_ROOT . "/vendor/bin/phpunit -c phpunit.xml.dist $extraArgs --list-tests", $outputLines);

        $validTestClasses = [];
        foreach ($outputLines as $outputLine) {
            if (!str_starts_with($outputLine, " - ")) {
                continue;
            }

            $outputLine = str_replace(" - ", "", $outputLine);

            $pieces = explode("::", $outputLine);
            $className = $pieces[0];

            $validTestClasses[$className] = true;
        }

        $validTestClasses = array_keys($validTestClasses);

        $filteredTestClasses = [];
        if ($this->excludeRegex !== null || $this->includeRegex !== null) {
            foreach ($validTestClasses as $validTestClass) {
                if ($this->excludeRegex && preg_match($this->excludeRegex, $validTestClass)) {
                    continue;
                }

                if ($this->includeRegex && !preg_match($this->includeRegex, $validTestClass)) {
                    continue;
                }

                $filteredTestClasses[] = $validTestClass;
            }
        } else {
            $filteredTestClasses = $validTestClasses;
        }

        // Filter for the offset
        $parallelTestCases = [];
        if ($this->offset !== null && $this->parallelism !== null) {
            foreach ($filteredTestClasses as $i => $filteredTestClass) {
                if ($i % $this->parallelism === $this->offset) {
                    $parallelTestCases[] = $filteredTestClass;
                }
            }
        } else {
            $parallelTestCases = $filteredTestClasses;
        }

        if ($this->outputRegex) {
            $quotedTests = array_map("preg_quote", $parallelTestCases);
            echo "/^(" . implode("|", $quotedTests) . ")/";
        } else {
            echo implode("\n", $parallelTestCases);
        }
    }

    /**
     * An offset for use with the --parallelism flag. This is a 0 indexed value for which runner in the parallel runners we are. If there is a --parallelism of 10, then this command should be run with offset 0 -> 9
     *
     * @param int $offset
     */
    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    /**
     * The number of parallel workers we are splitting tests for.
     *
     * @param int $parallelism
     */
    public function setParallelism(int $parallelism): void
    {
        $this->parallelism = $parallelism;
    }

    /**
     * Exclude any test classes matching this regex.
     *
     * @param string $excludeRegex
     */
    public function setExcludeRegex(string $excludeRegex): void
    {
        $this->excludeRegex = $excludeRegex;
    }

    /**
     * Inlcude only test classes matching this regex.
     *
     * @param string $includeRegex
     */
    public function setIncludeRegex(string $includeRegex): void
    {
        $this->includeRegex = $includeRegex;
    }

    /**
     * Output the result as a PHPUnit filter regex (for usage with phpunit's --filter argument).
     *
     * @param bool $outputRegex
     */
    public function setOutputRegex(bool $outputRegex): void
    {
        $this->outputRegex = $outputRegex;
    }
}
