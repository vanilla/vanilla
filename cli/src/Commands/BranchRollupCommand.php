<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Commands;

use Garden\Git\Exception\GitException;
use Garden\Git\Repository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

class BranchRollupCommand extends Command
{
    use ScriptLoggerTrait;

    /** @var array Track branches that were modified for pushing */
    private array $modifiedBranches = [];

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        parent::configure();
        $this->setName("branch-rollup")
            ->setDescription("Merge release branches upwards from a starting version")
            ->setDefinition(
                new InputDefinition([
                    new InputOption(
                        "release-version",
                        "r",
                        InputOption::VALUE_REQUIRED,
                        "The starting release version (e.g. 2025.009 - release/ prefix will be added automatically)"
                    ),
                    new InputOption(
                        "directory",
                        "d",
                        InputOption::VALUE_REQUIRED,
                        "Path to the git repository directory"
                    ),
                    new InputOption(
                        "no-interaction",
                        "n",
                        InputOption::VALUE_NONE,
                        "Do not ask any interactive questions - automatically proceed with all merges and pushes"
                    ),
                ])
            )
            ->setHelp(
                "This command merges changes from a release branch upwards through all subsequent release branches. The release/ prefix is automatically added to the version."
            );
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $startingVersionInput = $input->getOption("release-version");
        $directory = $input->getOption("directory");
        $noInteraction = $input->getOption("no-interaction");

        if (!$startingVersionInput) {
            $this->logger()->error("--release-version option is required");
            return Command::FAILURE;
        }

        if (!$directory) {
            $this->logger()->error("--directory option is required");
            return Command::FAILURE;
        }

        if ($noInteraction) {
            $this->logger()->info(
                "Running in <yellow>non-interactive mode</yellow> - all merges and pushes will proceed automatically"
            );
        }

        try {
            // Validate and get repository
            $repo = $this->validateAndGetRepository($directory);

            // Add release/ prefix if not present
            $startingVersion = $this->normalizeReleaseVersion($startingVersionInput);

            // Validate input and get starting branch
            $startingBranch = $this->validateAndGetStartingBranch($repo, $startingVersion);
            $this->logger()->info("Starting from branch: <yellow>{$startingBranch->getName()}</yellow>");

            // Get all subsequent branches
            $subsequentBranchNames = $this->getSubsequentBranches($repo, $startingBranch);

            if (empty($subsequentBranchNames)) {
                $this->logger()->success("No subsequent branches found to merge into.");
                return Command::SUCCESS;
            }

            $branchCount = count($subsequentBranchNames);
            $this->logger()->info("Found <yellow>{$branchCount}</yellow> subsequent branches to merge into:");
            foreach ($subsequentBranchNames as $branchName) {
                $this->logger()->info("  <yellow>- {$branchName}</yellow>");
            }

            // Ensure all necessary local branches exist (including starting branch if it was created from remote)
            $allBranchNames = array_merge([$startingBranch->getName()], $subsequentBranchNames);
            $this->ensureLocalBranchesExist($repo, $allBranchNames);

            // Reset modified branches tracking
            $this->modifiedBranches = [];

            // Perform the merges
            $this->performMerges($repo, $startingBranch, $subsequentBranchNames, $input, $output, $noInteraction);

            // Push all modified branches at the end
            $this->pushModifiedBranches($repo, $input, $output, $noInteraction);

            $this->logger()->success("All merges and pushes completed successfully!");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger()->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Normalize the release version by adding release/ prefix if not present.
     *
     * @param string $version
     * @return string
     */
    private function normalizeReleaseVersion(string $version): string
    {
        // If it already starts with release/, return as-is
        if (str_starts_with($version, "release/")) {
            return $version;
        }

        // Add the release/ prefix
        return "release/" . $version;
    }

    /**
     * Ensure local tracking branches exist for all branches we need to manipulate.
     *
     * @param Repository $repo
     * @param array $branchNames Array of branch names (not objects)
     * @throws \Exception
     */
    private function ensureLocalBranchesExist(Repository $repo, array $branchNames): void
    {
        foreach ($branchNames as $branchName) {
            // Check if local branch exists
            $localBranch = $repo->findBranch($branchName);
            if ($localBranch !== null) {
                continue; // Local branch already exists
            }

            // Check if remote branch exists
            $remoteBranchName = "origin/" . $branchName;
            try {
                $repo->git(["show-ref", "--verify", "--quiet", "refs/remotes/{$remoteBranchName}"]);

                // Create local tracking branch
                $this->logger()->info("Creating local tracking branch for <yellow>{$branchName}</yellow>");
                $repo->git(["checkout", "-t", $remoteBranchName]);
            } catch (GitException $e) {
                // Remote branch doesn't exist either
                throw new \Exception("Branch '{$branchName}' does not exist locally or on remote 'origin'");
            }
        }
    }

    /**
     * Validate directory and get repository instance.
     *
     * @param string $directory
     * @return Repository
     * @throws \Exception
     */
    private function validateAndGetRepository(string $directory): Repository
    {
        // Resolve absolute path
        $absoluteDirectory = realpath($directory);
        if (!$absoluteDirectory) {
            throw new \Exception("Directory does not exist: {$directory}");
        }

        // Check if it's our own repository
        $currentRepoPath = realpath(PATH_ROOT);
        if ($absoluteDirectory === $currentRepoPath) {
            throw new \Exception("Cannot manipulate the current repository. Please specify a different directory.");
        }

        // Validate it's a git repository
        if (!is_dir($absoluteDirectory . "/.git")) {
            throw new \Exception("Directory is not a git repository: {$absoluteDirectory}");
        }

        return new Repository($absoluteDirectory);
    }

    /**
     * Get the repository instance.
     *
     * @return Repository
     */
    public function getRepository(): Repository
    {
        $repo = new Repository(PATH_ROOT);
        return $repo;
    }

    /**
     * Validate the starting version and return the branch.
     *
     * @param Repository $repo
     * @param string $version
     * @return \Garden\Git\Branch
     * @throws \Exception
     */
    private function validateAndGetStartingBranch(Repository $repo, string $version): \Garden\Git\Branch
    {
        // Validate format (should now include release/ prefix)
        if (!preg_match('/^release\/(\d{4})\.(\d{3})$/', $version, $matches)) {
            throw new \Exception("Invalid version format. Expected format: YYYY.XXX (e.g., 2025.009)");
        }

        $year = (int) $matches[1];
        $number = (int) $matches[2];

        // Validate reasonable year range
        $currentYear = (int) date("Y");
        if ($year < 2020 || $year > $currentYear + 5) {
            throw new \Exception("Year must be between 2020 and " . ($currentYear + 5));
        }

        // Validate number range
        if ($number < 1 || $number > 999) {
            throw new \Exception("Release number must be between 001 and 999");
        }

        // Check if branch exists locally
        $branch = $repo->findBranch($version);
        if ($branch) {
            return $branch;
        }

        // Check if remote branch exists and create local tracking branch
        $remoteBranchName = "origin/" . $version;
        try {
            $repo->git(["show-ref", "--verify", "--quiet", "refs/remotes/{$remoteBranchName}"]);

            // Create local tracking branch
            $this->logger()->info("Creating local tracking branch for {$version}");
            $repo->git(["checkout", "-t", $remoteBranchName]);

            $branch = $repo->getBranch($version);
            return $branch;
        } catch (GitException $e) {
            throw new \Exception("Branch '{$version}' does not exist locally or on remote 'origin'");
        }
    }

    /**
     * Get all subsequent release branches.
     *
     * @param Repository $repo
     * @param \Garden\Git\Branch $startingBranch
     * @return array
     */
    private function getSubsequentBranches(Repository $repo, \Garden\Git\Branch $startingBranch): array
    {
        // First, fetch from remote to get the latest branch information
        try {
            $this->logger()->info("Fetching latest branches from remote...");
            $repo->git(["fetch", "origin"]);
        } catch (GitException $e) {
            $this->logger()->warning("⚠ Could not fetch from remote: " . $e->getMessage());
        }

        // Get both local and remote branches
        $allBranches = $repo->getBranches();
        $remoteBranches = $this->getRemoteBranches($repo);

        $releaseBranches = [];

        // Extract starting branch info
        preg_match('/^release\/(\d{4})\.(\d{3})$/', $startingBranch->getName(), $startingMatches);
        $startingYear = (int) $startingMatches[1];
        $startingNumber = (int) $startingMatches[2];

        // Check local branches
        foreach ($allBranches as $branch) {
            $branchName = $branch->getName();

            // Only consider release branches
            if (!preg_match('/^release\/(\d{4})\.(\d{3})$/', $branchName, $matches)) {
                continue;
            }

            $year = (int) $matches[1];
            $number = (int) $matches[2];

            // Check if this branch is subsequent to the starting branch
            if ($this->isBranchSubsequent($startingYear, $startingNumber, $year, $number)) {
                $releaseBranches[$branchName] = [
                    "branchName" => $branchName,
                    "year" => $year,
                    "number" => $number,
                    "sortKey" => sprintf("%04d.%03d", $year, $number),
                    "isLocal" => true,
                ];
            }
        }

        // Check remote branches and add any that don't exist locally
        foreach ($remoteBranches as $remoteBranchName) {
            // Remove 'origin/' prefix to get the branch name
            $branchName = str_replace("origin/", "", $remoteBranchName);

            // Skip if we already have this branch locally
            if (isset($releaseBranches[$branchName])) {
                continue;
            }

            // Only consider release branches
            if (!preg_match('/^release\/(\d{4})\.(\d{3})$/', $branchName, $matches)) {
                continue;
            }

            $year = (int) $matches[1];
            $number = (int) $matches[2];

            // Check if this branch is subsequent to the starting branch
            if ($this->isBranchSubsequent($startingYear, $startingNumber, $year, $number)) {
                // Create a pseudo-branch object for remote branches
                $releaseBranches[$branchName] = [
                    "branchName" => $branchName,
                    "year" => $year,
                    "number" => $number,
                    "sortKey" => sprintf("%04d.%03d", $year, $number),
                    "isLocal" => false,
                    "remoteName" => $remoteBranchName,
                ];
            }
        }

        // Sort branches by version
        uasort($releaseBranches, function ($a, $b) {
            return strcmp($a["sortKey"], $b["sortKey"]);
        });

        // Return just the branch names - we'll resolve them to actual branches later
        return array_values(
            array_map(function ($branchInfo) {
                return $branchInfo["branchName"];
            }, $releaseBranches)
        );
    }

    /**
     * Get remote release branches.
     *
     * @param Repository $repo
     * @return array
     */
    private function getRemoteBranches(Repository $repo): array
    {
        try {
            $output = $repo->git(["branch", "-r", "--list", "origin/release/*"]);
            $lines = array_filter(explode("\n", trim($output)));

            $branches = [];
            foreach ($lines as $line) {
                $line = trim($line);
                // Remove any leading characters and whitespace
                $line = preg_replace("/^\s*\*?\s*/", "", $line);
                if (!empty($line) && strpos($line, "origin/release/") === 0) {
                    $branches[] = $line;
                }
            }

            return $branches;
        } catch (GitException $e) {
            throw new \Exception("⚠ Could not get remote branches: " . $e->getMessage());
        }
    }

    /**
     * Check if a branch is subsequent to the starting branch.
     *
     * @param int $startingYear
     * @param int $startingNumber
     * @param int $year
     * @param int $number
     * @return bool
     */
    private function isBranchSubsequent(int $startingYear, int $startingNumber, int $year, int $number): bool
    {
        if ($year > $startingYear) {
            return true;
        }

        if ($year == $startingYear && $number > $startingNumber) {
            return true;
        }

        return false;
    }

    /**
     * Perform the merges from starting branch through all subsequent branches.
     *
     * @param Repository $repo
     * @param \Garden\Git\Branch $startingBranch
     * @param array $subsequentBranchNames Array of branch names
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $noInteraction
     * @throws \Exception
     */
    private function performMerges(
        Repository $repo,
        \Garden\Git\Branch $startingBranch,
        array $subsequentBranchNames,
        InputInterface $input,
        OutputInterface $output,
        bool $noInteraction
    ): void {
        $currentBranch = $repo->currentBranch();

        try {
            $previousBranch = $startingBranch;

            foreach ($subsequentBranchNames as $targetBranchName) {
                // Get the actual branch object
                $targetBranch = $repo->getBranch($targetBranchName);

                $this->logger()->info(
                    "Preparing merge: <yellow>{$previousBranch->getName()}</yellow> into <yellow>{$targetBranch->getName()}</yellow>"
                );

                // Switch to target branch
                $repo->switchBranch($targetBranch);

                // Check if merge is needed and preview commits
                $commitCount = $this->previewMerge($repo, $previousBranch, $targetBranch, $output);

                if ($commitCount === 0) {
                    $this->logger()->info(
                        "  <green>{$targetBranch->getName()} is already up to date with {$previousBranch->getName()}</green>"
                    );
                    $previousBranch = $targetBranch;
                    continue;
                }

                // Ask for confirmation or proceed automatically
                $shouldProceed = $noInteraction;
                if (!$noInteraction) {
                    $helper = $this->getHelper("question");
                    $question = new ConfirmationQuestion(
                        "Do you want to merge <comment>{$previousBranch->getName()}</comment> into <comment>{$targetBranch->getName()}</comment> (<comment>{$commitCount}</comment> new commits)? [y/N] ",
                        false
                    );
                    $shouldProceed = $helper->ask($input, $output, $question);
                } else {
                    $this->logger()->info(
                        "Auto-proceeding with merge of <yellow>{$previousBranch->getName()}</yellow> into <yellow>{$targetBranch->getName()}</yellow> (<yellow>{$commitCount}</yellow> new commits)"
                    );
                }

                if (!$shouldProceed) {
                    $this->logger()->warning("⚠ Skipping merge for {$targetBranch->getName()}");
                    $previousBranch = $targetBranch;
                    continue;
                }

                // Perform the merge
                $this->performMerge($repo, $previousBranch, $targetBranch);

                // Track this branch as modified
                $this->modifiedBranches[] = [
                    "branch" => $targetBranch,
                    "commitCount" => $commitCount + 1, // +1 for the merge commit
                ];

                $previousBranch = $targetBranch;
            }
        } finally {
            // Switch back to original branch
            try {
                $repo->switchBranch($currentBranch);
                $this->logger()->info("Switched back to original branch: <yellow>{$currentBranch->getName()}</yellow>");
            } catch (\Exception $e) {
                $this->logger()->warning("⚠ Could not switch back to original branch: " . $e->getMessage());
            }
        }
    }

    /**
     * Preview what commits would be merged.
     *
     * @param Repository $repo
     * @param \Garden\Git\Branch $sourceBranch
     * @param \Garden\Git\Branch $targetBranch
     * @param OutputInterface $output
     * @return int Number of commits that would be merged
     */
    private function previewMerge(
        Repository $repo,
        \Garden\Git\Branch $sourceBranch,
        \Garden\Git\Branch $targetBranch,
        OutputInterface $output
    ): int {
        // Check if we're already up to date
        $mergeBase = trim($repo->git(["merge-base", $sourceBranch->getName(), $targetBranch->getName()]));
        $sourceCommit = trim($repo->git(["rev-parse", $sourceBranch->getName()]));

        if ($mergeBase === $sourceCommit) {
            return 0; // Up to date
        }

        // Get commits that would be merged
        $commits = $repo->git([
            "log",
            "--oneline",
            "--no-merges",
            "{$targetBranch->getName()}..{$sourceBranch->getName()}",
        ]);
        $commits = trim($commits);

        if (empty($commits)) {
            return 0;
        }

        $commitLines = explode("\n", $commits);
        $commitCount = count($commitLines);

        $this->logger()->info("  Commits to be merged (<yellow>{$commitCount}</yellow>):");
        foreach ($commitLines as $commit) {
            // Split commit hash and message
            $parts = explode(" ", $commit, 2);
            $hash = $parts[0] ?? "";
            $message = $parts[1] ?? "";
            $this->logger()->info("    <yellow>{$hash}</yellow> {$message}");
        }

        return $commitCount;
    }

    /**
     * Perform a single merge operation.
     *
     * @param Repository $repo
     * @param \Garden\Git\Branch $sourceBranch
     * @param \Garden\Git\Branch $targetBranch
     * @throws \Exception
     */
    private function performMerge(
        Repository $repo,
        \Garden\Git\Branch $sourceBranch,
        \Garden\Git\Branch $targetBranch
    ): void {
        try {
            // Perform the merge
            $mergeOutput = $repo->git([
                "merge",
                "-m",
                "Merge {$sourceBranch->getName()} into {$targetBranch->getName()}",
                $sourceBranch->getName(),
            ]);

            $this->logger()->success(
                "  Successfully merged <yellow>{$sourceBranch->getName()}</yellow> into <yellow>{$targetBranch->getName()}</yellow>"
            );
        } catch (GitException $e) {
            // Check if it's a conflict
            if (
                strpos($e->getMessage(), "CONFLICT") !== false ||
                strpos($e->getMessage(), "Automatic merge failed") !== false
            ) {
                throw new \Exception(
                    "Merge conflict detected when merging {$sourceBranch->getName()} into {$targetBranch->getName()}. Please resolve conflicts manually and try again."
                );
            }
            throw new \Exception("Git merge failed: " . $e->getMessage());
        }
    }

    /**
     * Push all modified branches after confirmation.
     *
     * @param Repository $repo
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param bool $noInteraction
     * @throws \Exception
     */
    private function pushModifiedBranches(
        Repository $repo,
        InputInterface $input,
        OutputInterface $output,
        bool $noInteraction
    ): void {
        if (empty($this->modifiedBranches)) {
            $this->logger()->info("No branches were modified, nothing to push.");
            return;
        }

        $this->logger()->info("📋 Summary of branches to push:");
        $totalCommits = 0;
        foreach ($this->modifiedBranches as $modifiedBranch) {
            $branch = $modifiedBranch["branch"];
            $commitCount = $modifiedBranch["commitCount"];
            $totalCommits += $commitCount;
            $this->logger()->info(
                "  <yellow>- {$branch->getName()}</yellow> (<yellow>{$commitCount}</yellow> new commits)"
            );
        }

        // Ask for final confirmation or proceed automatically
        $shouldProceed = $noInteraction;
        if (!$noInteraction) {
            $helper = $this->getHelper("question");
            $branchCount = count($this->modifiedBranches);
            $question = new ConfirmationQuestion(
                "🚀 Push <comment>{$branchCount}</comment> modified branches with <comment>{$totalCommits}</comment> total new commits? [y/N] ",
                false
            );
            $shouldProceed = $helper->ask($input, $output, $question);
        } else {
            $branchCount = count($this->modifiedBranches);
            $this->logger()->info(
                "Auto-proceeding with push of <yellow>{$branchCount}</yellow> modified branches with <yellow>{$totalCommits}</yellow> total new commits"
            );
        }

        if (!$shouldProceed) {
            $this->logger()->warning("⚠ Push cancelled by user.");
            return;
        }

        // Perform all pushes
        foreach ($this->modifiedBranches as $modifiedBranch) {
            $branch = $modifiedBranch["branch"];
            $this->pushBranch($repo, $branch);
        }
    }

    /**
     * Push a branch to its remote.
     *
     * @param Repository $repo
     * @param \Garden\Git\Branch $branch
     * @throws \Exception
     */
    private function pushBranch(Repository $repo, \Garden\Git\Branch $branch): void
    {
        try {
            // Get the default remote (usually 'origin')
            $remotes = $repo->getRemotes();
            $remote = null;

            foreach ($remotes as $r) {
                if ($r->getName() === "origin") {
                    $remote = $r;
                    break;
                }
            }

            if (!$remote && !empty($remotes)) {
                $remote = $remotes[0]; // Use first available remote
            }

            if (!$remote) {
                throw new \Exception("No remote found to push to");
            }

            $repo->pushBranch($branch, $remote);
            $this->logger()->success(
                "  ✓ Pushed <yellow>{$branch->getName()}</yellow> to <yellow>{$remote->getName()}</yellow>"
            );
        } catch (GitException $e) {
            throw new \Exception("Failed to push {$branch->getName()}: " . $e->getMessage());
        }
    }
}
