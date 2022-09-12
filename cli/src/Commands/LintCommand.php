<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

/**
 * Linting utilities.
 */
class LintCommand
{
    /** @var string */
    private $comparisonBranch;

    /**
     * Lint changed PHP code.
     */
    public function lint()
    {
        $root = PATH_ROOT;
        system(PATH_ROOT . "/.circleci/scripts/diff-standards.sh {$this->comparisonBranch} {$root}");
    }

    /**
     * @return string
     */
    public function getComparisonBranch(): string
    {
        return $this->comparisonBranch ?? "master";
    }

    /**
     * The branch to compare to. Defaults to 'master'.
     *
     * @param string $comparisonBranch
     */
    public function setComparisonBranch(string $comparisonBranch): void
    {
        $this->comparisonBranch = $comparisonBranch;
    }
}
