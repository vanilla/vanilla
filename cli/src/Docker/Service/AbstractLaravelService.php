<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Garden\Git\Repository;
use Vanilla\Cli\Commands\DockerContainerCommand;
use Vanilla\Cli\Commands\DockerJobberCommand;
use Vanilla\Cli\Commands\DockerLaravelCommand;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\InstallDataTrait;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellUtils;
use Vanilla\FileUtils;
use Webmozart\PathUtil\Path;

/**
 * Extended version of {@link AbstractService} for Laravel services.
 */
abstract class AbstractLaravelService extends AbstractService
{
    use InstallDataTrait;
    use ScriptLoggerTrait;

    /** @var bool Set to true if we just cloned the repository. */
    protected bool $didJustClone = false;

    public function __construct(public LaravelServiceDescriptor $laravelDescriptor)
    {
        parent::__construct($laravelDescriptor);
    }

    /**
     * @inheritdoc
     */
    public function getContainerCommands(): array
    {
        return [new DockerLaravelCommand($this)];
    }

    /**
     * Get the install config to track that we cloned the service.
     *
     * @return string
     */
    public function getInstallConfig(): string
    {
        return "docker.{$this->descriptor->serviceID}.wasCloned";
    }

    /**
     * @return string
     */
    public function getComposeFileDirectory(): string
    {
        // Get just the reponame off a git url.
        // Ex, "git@github.com:vanilla/vnla-jobber.git" -> "vnla-jobber"
        $gitUrl = $this->laravelDescriptor->gitUrl;
        $repoName = basename($gitUrl, ".git");

        return Path::canonicalize(PATH_ROOT . "/../$repoName");
    }

    /**
     * @inheritdoc
     */
    public function start(): void
    {
        $this->startDocker();
        $this->composerInstall();

        $this->reloadNginx();
        $this->finishStart();
    }

    /**
     * @inheritdoc
     */
    public function ensureCloned(): void
    {
        $isEmpty = $this->isDirEmpty($this->getComposeFileDirectory());
        if (!file_exists($this->getComposeFileDirectory()) || $isEmpty) {
            if ($isEmpty) {
                chmod($this->getComposeFileDirectory(), 0777);
                FileUtils::deleteRecursively($this->getComposeFileDirectory());
            }

            $this->logger()->title("Cloning '{$this->descriptor->label}'");
            $process = new Process([
                "git",
                "clone",
                $this->laravelDescriptor->gitUrl,
                $this->getComposeFileDirectory(),
            ]);
            $this->logger()->runProcess($process);
            // Ensure the cache directory is writeable.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getComposeFileDirectory() . "/storage/framework")
            );

            foreach ($iterator as $item) {
                chmod($item, 0777);
            }
            $this->installData()->set($this->getInstallConfig(), true);
            $this->tryToSetToSameBranch();
            $this->didJustClone = true;
        }

        // It already exists.
        $this->installData()->set($this->getInstallConfig(), true);
        // Make sure we have the latest version of the queue.
        $this->logger()->title("Pulling latest version of '{$this->descriptor->label}'");
        $process = new Process(["git", "pull"], $this->getComposeFileDirectory());
        try {
            $this->logger()->runProcess($process);
            $this->logger()->success("Latest '{$this->descriptor->label}' Pulled");
        } catch (\Throwable $throwable) {
            $this->logger()->warning("'{$this->descriptor->label}' did not pull successfully.");
        }
    }

    /**
     * Check if a directory exists and is empty.
     *
     * @param string $dir
     *
     * @return bool
     */
    private function isDirEmpty(string $dir): bool
    {
        return file_exists($dir) && count(scandir($dir)) == 2;
    }

    /**
     * Attempt to pull the same branch from the other repo.
     */
    private function tryToSetToSameBranch(): void
    {
        $ownRepo = new Repository(PATH_ROOT);
        $targetRepo = new Repository($this->getComposeFileDirectory());

        $ownBranch = $ownRepo->currentBranch();
        $targetBranch = $targetRepo->currentBranch();

        if ($ownBranch->getName() == $targetBranch->getName()) {
            // Nothing to do.
            return;
        }

        // Check if we have the branch on the other repo.
        $foundBranch = $targetRepo->findBranch($ownBranch);
        if ($foundBranch == null) {
            // No matching branch.
            return;
        }

        $result = ShellUtils::promptYesNo(
            "Found branch matching '{$ownBranch->getName()}' on '{$this->descriptor->label}' do you want to switch to it?"
        );
        if ($result) {
            $this->logger()->info("Checking out branch {$ownBranch->getName()}");
        }
        $targetRepo->switchBranch($foundBranch);
    }

    /**
     * Run composer install on the project inside the container.
     *
     * @return void
     */
    private function composerInstall(): void
    {
        $this->logger()->title("{$this->descriptor->label} - Composer Install");
        $hadVendorDirectory = file_exists($this->getComposeFileDirectory() . "/vendor");
        DockerUtils::composer(
            $this->getComposeFileDirectory(),
            $this->descriptor->containerName,
            "/var/www/html",
            "install"
        );

        if (!$hadVendorDirectory) {
            // After the first composer install restart the container.
            DockerUtils::restartContainer($this->getComposeFileDirectory(), $this->descriptor->containerName);

            DockerUtils::artisan(
                $this->getComposeFileDirectory(),
                $this->descriptor->containerName,
                "/var/www/html",
                "vendor:publish --tag=telescope-assets --force"
            );
        }
    }

    /**
     * Run database migrations for the service.
     *
     * @return void
     */
    protected function artisanMigrate(): void
    {
        // Create the database if it does not exist.
        $this->logger()->info("Ensuring database is migrated");
        $envContents = $this->getEnvFileContents();
        $this->getPdo()->exec("CREATE DATABASE IF NOT EXISTS {$envContents["DB_DATABASE"]}");

        // Make sure our tables are migrated
        DockerUtils::containerCommand(
            $this->getComposeFileDirectory(),
            $this->descriptor->containerName,
            "/var/www/html",
            "./artisan migrate"
        );
    }

    /**
     * Perform an initial npm install and build on a freshly cloned repo.
     *
     * @return void
     */
    protected function doInitialNpmInstallAndBuild(): void
    {
        if ($this->didJustClone) {
            $this->logger()->info("Doing initial NPM install & build");
            DockerUtils::containerCommand(
                $this->getComposeFileDirectory(),
                $this->descriptor->containerName,
                "/var/www/html",
                "npm install && npm run prod"
            );
        }
    }

    /**
     * Get a configured PDO instance on to the service's database.
     *
     * @return \PDO
     */
    protected function getPdo(): \PDO
    {
        $envContents = $this->getEnvFileContents();
        $pdo = new \PDO(
            "mysql:host={$envContents["DB_HOST"]};port={$envContents["DB_PORT"]}",
            $envContents["DB_USERNAME"],
            $envContents["DB_PASSWORD"]
        );
        return $pdo;
    }
}
