<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Cloud\Commands;

use Vanilla\Cli\Utils\ShellException;
use Vanilla\Cli\Utils\ShellUtils;
use Vanilla\Cli\Utils\SimpleScriptLogger;

/**
 * Script for syncing vanilla/vanilla-cloud to vanilla/vanilla.
 */
class SyncOssCommand
{
    const VERSION = "1.0.0";
    const OSS_ORIGIN = "vanilla-oss";

    private const DEFAULT_OSS_BASE = "master";
    private const DEFAULT_CLOUD_BASE = "master";

    /** @var string Cloud branch with which to base the sync. */
    private $cloudBase = self::DEFAULT_CLOUD_BASE;

    /** @var bool Does the cloud branch have a remote? */
    private $cloudHasRemote = false;

    /** @var SimpleScriptLogger */
    private $logger;

    /** @var string OSS branch to use as a base for the target of the sync. */
    private $ossBase = self::DEFAULT_OSS_BASE;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->logger = new SimpleScriptLogger();

        // Kludge to make sure this is loaded before we start changing repos and the cloud directory vanishes.
        $kludge = new ShellException();
    }

    const SYNC_EXCLUDE_LIST = [
        // Not really paths.
        ".",
        "..",
        ".git",

        // Cloud exclusive. SHOULD NEVER SYNC.
        "cloud",
        "cli/src/Cloud",

        // Vendors. Should already be done by .gitignore, but just in case.
        "vendor",
        "node_modules",

        // Actually different and maintained separately.
        ".circleci",
        ".github",
        "phpunit.xml.dist",
        "README.md",

        // .gitignored but just in case.
        ".DS_STORE",
        ".idea",
        ".phpunit.result.cache",
        "cgi-bin",
        "dist",
        "git-diff.txt",
        "phpcs-diff.json",
        "phpunit.xml",
    ];

    /**
     * Get the current Git branch.
     *
     * @return string
     */
    private function currentBranch(): string
    {
        $resultCode = null;
        $output = ShellUtils::command(
            "git rev-parse --abbrev-ref HEAD",
            [],
            "Unable to determine the current Git branch.",
            $this->logger
        );
        return reset($output);
    }

    /**
     * Get the currently-configured base OSS branch.
     *
     * @return string
     */
    public function getOssBase(): string
    {
        return $this->ossBase;
    }

    /**
     * Set the OSS base branch.
     *
     * @param string $ossBase
     */
    public function setOssBase(string $ossBase): void
    {
        $this->ossBase = $ossBase;
    }

    /**
     * Get the currently-configured base cloud branch.
     *
     * @return string
     */
    public function getCloudBase(): string
    {
        return $this->cloudBase;
    }

    /**
     * Set the cloud base branch.
     *
     * @param string $cloudBase
     */
    public function setCloudBase(string $cloudBase): void
    {
        $this->cloudBase = $cloudBase;
    }

    /**
     * Does the specified branch have a remote associated with it?
     *
     * @param string $branch
     * @return bool
     */
    private function branchHasRemote(string $branch): bool
    {
        $output = ShellUtils::command(
            "git branch --list --format=\"%%(upstream)\" %s",
            [$branch],
            "Failed to obtain remote status of cloud branch.",
            $this->logger
        );

        if (count($output) > 1) {
            throw new ShellException("Unable to determine remote status. Branch name ambiguous: {$branch}");
        }

        return !empty(trim(reset($output)));
    }

    /**
     * Sync common commits from the vanilla-cloud repo to the vanilla repository.
     */
    public function syncOss()
    {
        $this->cloudHasRemote = $this->branchHasRemote($this->getCloudBase());
        $currentBranch = $this->currentBranch();

        try {
            $this->logger->title("Vanilla OSS SyncTool");
            $this->logger->info("Version: " . self::VERSION);

            // Switch to root directory.
            ShellUtils::shellOrThrow("cd " . $this->getRootDir());
            $this->gitIntegrityCheck();
            $this->ensureOriginCreated();
            $this->createBranch();
        } finally {
            $this->cleanup($currentBranch);
        }
    }

    /**
     * Cleanup function for the script.
     *
     * @param string $branch
     */
    private function cleanup(string $branch = "master"): void
    {
        ShellUtils::command("git checkout %s", [$branch], null, $this->logger);
    }

    /**
     * Get the root directory of vanilla.
     *
     * @return string
     */
    private function getRootDir(): string
    {
        return realpath(__DIR__ . "/../../../..");
    }

    /**
     * Validate that there the git status is clean. Exits otherwise.
     */
    private function gitIntegrityCheck(): void
    {
        $this->logger->title("Validating integrity of the git repo");

        $gitStatus = implode(PHP_EOL, ShellUtils::command("git status", [], null, $this->logger));

        $isDirty = strpos($gitStatus, "working tree clean") !== false;
        if ($isDirty) {
            $this->logger->success("Working tree is clean.");
        } else {
            $this->logger->error("Git working tree is dirty. Unable to proceed.", [
                SimpleScriptLogger::CONTEXT_LINE_COUNT => 2,
            ]);
            $this->logger->info($gitStatus);
            die(1);
        }
    }

    /**
     * Ensure that our remote origin for vanilla/vanilla is created.
     */
    private function ensureOriginCreated(): void
    {
        $OSS_ORIGIN = self::OSS_ORIGIN;
        $this->logger->title("Validating Git Origins");

        $existingOrigins = implode(PHP_EOL, ShellUtils::command("git remote -v", [], null, $this->logger));
        $hasOrigin = strpos($existingOrigins, $OSS_ORIGIN) !== false;

        if ($hasOrigin) {
            $this->logger->info("Found existing remote origin $OSS_ORIGIN");
        } else {
            $this->logger->info("Could not find existing remote origin $OSS_ORIGIN. Creating it now.", [
                SimpleScriptLogger::CONTEXT_LINE_COUNT => 2,
            ]);
            ShellUtils::command(
                "git remote add %s git@github.com:vanilla/vanilla.git",
                [$OSS_ORIGIN],
                null,
                $this->logger
            );
        }
    }

    /**
     * Create a new branch with the synced changes.
     */
    private function createBranch()
    {
        $OSS_ORIGIN = self::OSS_ORIGIN;
        $currentTime = new \DateTime();
        $dateStamp = $currentTime->format("Y-m-d");
        $timeInt = $currentTime->getTimestamp();
        $branchName = "sync/$dateStamp-$timeInt";

        $this->logger->title("Ensuring remote branches are up to date");
        ShellUtils::command("git fetch --all", [], null, $this->logger);

        $this->logger->title("Creating New Sync Branch");
        $this->logger->info("Branch name will be: $branchName", [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);

        // Gather all the directories to sync.
        $pathSpec = $this->gatherPathSpecToSync();

        $cloudBase = $this->cloudHasRemote ? "origin/" . $this->getCloudBase() : $this->getCloudBase();
        $ossBase = $OSS_ORIGIN . "/" . $this->getOssBase();

        ShellUtils::command("git checkout -b %s %s", [$branchName, $ossBase], null, $this->logger);

        $this->syncDeletedFiles($ossBase, $cloudBase);

        ShellUtils::command(
            "git checkout %s --" . str_repeat(" %s", count($pathSpec)),
            array_merge([$cloudBase], $pathSpec),
            null,
            $this->logger
        );

        ShellUtils::command("git add . && git commit -m %s", ["Syncing files from upstream."], null, $this->logger);

        $this->logger->info("git push $OSS_ORIGIN $branchName");
    }

    /**
     * Make sure deleted/moved files are handled during the sync.
     *
     * @param string $revision1
     * @param string $revision2
     */
    private function syncDeletedFiles(string $revision1, string $revision2)
    {
        $brokenLines = ShellUtils::command(
            "git diff --name-status --diff-filter=DR %s..%s",
            [$revision1, $revision2],
            null,
            $this->logger
        );
        $deletedFiles = [];

        foreach ($brokenLines as $brokenLine) {
            if (!trim($brokenLine)) {
                continue;
            }

            preg_match("/([^\s]+)\s+(?<deletedPath>[^\s]+)(\s+)?([^\s]*)?/", $brokenLine, $matches);
            $deletedPath = $matches["deletedPath"] ?? null;
            if (!trim($deletedPath)) {
                continue;
            }

            $fullDeletedPath = PATH_ROOT . "/" . $deletedPath;
            $deletedFiles[] = $fullDeletedPath;
        }

        if (count($deletedFiles) > 0) {
            $this->logger->title("The following files were deleted or moved.");
            $this->logger->info(implode("\n", $deletedFiles));

            ShellUtils::command(
                "git rm -f" . str_repeat(" %s", count($deletedFiles)),
                $deletedFiles,
                null,
                $this->logger
            );
        } else {
            $this->logger->title("No files were deleted or moved");
        }
    }

    /**
     * Get the pathspec to pass for git of the files to copy.
     *
     * @return string
     */
    private function gatherPathSpecToSync(): array
    {
        $this->logger->title("Gathering paths to sync");

        if ($this->cloudHasRemote) {
            $this->logger->info("Updating to latest cloud branch revision.");

            ShellUtils::command("git checkout %s", [$this->getCloudBase()], null, $this->logger);

            ShellUtils::command("git pull", [], null, $this->logger);
        }

        $this->gitIntegrityCheck();

        $paths = scandir($this->getRootDir());
        $allowedPaths = [];
        $excludedPaths = [];
        foreach ($paths as $path) {
            if (!in_array($path, self::SYNC_EXCLUDE_LIST)) {
                $allowedPaths[] = $path;
            } elseif ($path !== "." && $path !== "..") {
                $excludedPaths[] = $path;
            }
        }

        $this->logger->alert("The following paths will be synced:");
        $this->logger->success(implode("\n", $allowedPaths), [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);
        $this->logger->alert("The following paths will be excluded:");
        $this->logger->error(implode("\n", $excludedPaths), [SimpleScriptLogger::CONTEXT_LINE_COUNT => 2]);
        $this->logger->promptContinue("Do they look correct?");

        return $allowedPaths;
    }
}
