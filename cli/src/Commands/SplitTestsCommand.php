<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

/**
 * Command to split up php-unit tests.
 */
class SplitTestsCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName("split-tests")
            ->setDescription(
                "Command for splitting up looking up and splitting PHPUnit tests classes for parallel testing in CI."
            )
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputOption(
                        "include-regex",
                        null,
                        Console\Input\InputOption::VALUE_REQUIRED,
                        "Include only test classes matching this regex."
                    ),
                    new Console\Input\InputOption(
                        "exclude-regex",
                        null,
                        Console\Input\InputOption::VALUE_REQUIRED,
                        "Exclude any test classes matching this regex."
                    ),
                    new Console\Input\InputOption(
                        "output-regex",
                        null,
                        Console\Input\InputOption::VALUE_NONE,
                        "Output the result as a PHPUnit filter regex (for usage with phpunit's --filter argument)."
                    ),
                    new Console\Input\InputOption(
                        "parallelism",
                        null,
                        Console\Input\InputOption::VALUE_REQUIRED,
                        "The number of parallel workers we are splitting tests for."
                    ),
                    new Console\Input\InputOption(
                        "offset",
                        null,
                        Console\Input\InputOption::VALUE_REQUIRED,
                        "An offset for use with the --parallelism flag. This is a 0 indexed value for which runner in the parallel runners we are. If there is a --parallelism of 10, then this command should be run with offset 0 -> 9"
                    ),
                ])
            )
            ->addUsage(
                '--include-regex="/OnlyMatchThese/" --exclude-regex="/Don\'tMatchThese/" --output-regex --parallelism=10 --offset=0'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputRegex = $input->getOption("output-regex") ?? false;

        $validTestClasses = $this->getTestClasses();
        $this->logger()->debug("Valid test classes:\n" . implode("\n", $validTestClasses));
        $filteredTextClasses = $this->filterRegexTestClasses($validTestClasses, $input);
        $parallelTestCases = $this->splitTestClasses($filteredTextClasses, $input);

        if ($outputRegex) {
            $quotedTests = array_map("preg_quote", $parallelTestCases);
            echo "/^(" . implode("|", $quotedTests) . ")/";
        } else {
            echo implode("\n", $parallelTestCases);
        }

        return Console\Command\Command::SUCCESS;
    }

    /**
     * Get valid test classes from PHPUnit.
     *
     * @return string[]
     */
    private function getTestClasses(): array
    {
        exec(PATH_ROOT . "/vendor/bin/phpunit -c phpunit.xml.dist --list-tests", $outputLines);

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
        return $validTestClasses;
    }

    /**
     * Filter test classes by the include/exclude regex's
     *
     * @param string[] $validTestClasses
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function filterRegexTestClasses(array $validTestClasses, InputInterface $input): array
    {
        $excludeRegex = $input->getOption("exclude-regex");
        $includeRegex = $input->getOption("include-regex");
        $filteredTestClasses = [];
        if ($excludeRegex !== null || $includeRegex !== null) {
            foreach ($validTestClasses as $validTestClass) {
                if ($excludeRegex && preg_match($excludeRegex, $validTestClass)) {
                    continue;
                }

                if ($includeRegex && !preg_match($includeRegex, $validTestClass)) {
                    continue;
                }

                $filteredTestClasses[] = $validTestClass;
            }
        } else {
            $filteredTestClasses = $validTestClasses;
        }

        return $filteredTestClasses;
    }

    /**
     * Filter test classes to ones that should run in given parallel worker.
     *
     * @param array $testClasses
     * @param InputInterface $input
     *
     * @return string[]
     */
    private function splitTestClasses(array $testClasses, InputInterface $input)
    {
        $offset = $input->getOption("offset");
        $parallelism = $input->getOption("parallelism");

        if (!$parallelism) {
            return $testClasses;
        }

        if (!is_numeric($parallelism)) {
            throw new \Exception("--parallelism must be a numeric value.");
        } else {
            $parallelism = (int) $parallelism;
        }

        if ($offset === null) {
            throw new \Exception("--offset must be provided with --parallelism");
        }

        if (!is_numeric($offset)) {
            throw new \Exception("--offset must be a numeric value.");
        } else {
            $offset = (int) $offset;
        }

        // Filter for the offset
        $parallelTestCases = [];
        foreach ($testClasses as $i => $filteredTestClass) {
            if ($i % $parallelism === $offset) {
                $parallelTestCases[] = $filteredTestClass;
            }
        }

        return $parallelTestCases;
    }
}
