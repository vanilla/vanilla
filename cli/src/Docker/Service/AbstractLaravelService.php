<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Garden\Git\Repository;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\InstallDataTrait;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\Cli\Utils\ShellUtils;
use Vanilla\FileUtils;

abstract class AbstractLaravelService extends AbstractService
{
    use InstallDataTrait;
    use ScriptLoggerTrait;

    /**
     * Get the git url to clone from.
     *
     * @return string
     */
    abstract public function getGitUrl(): string;

    /**
     * Get the install config to track that we cloned the service.
     *
     * @return string
     */
    abstract public function getInstallConfig(): string;

    /**
     * Get the container name of the service.
     */
    abstract public function getContainerName(): string;

    /**
     * @inheritDoc
     */
    public function start()
    {
        $this->startDocker();
        $this->finishStart();
        $this->composerInstall();
        $this->reloadNginx();
        $this->finishStart();
    }

    /**
     * @inheritDoc
     */
    public function ensureCloned()
    {
        $isEmpty = $this->isDirEmpty($this->getTargetDirectory());
        if (!file_exists($this->getTargetDirectory()) || $isEmpty) {
            if ($isEmpty) {
                chmod($this->getTargetDirectory(), 0777);
                FileUtils::deleteRecursively($this->getTargetDirectory());
            }

            $this->logger()->title("Cloning '{$this->getName()}'");
            $process = new Process(["git", "clone", $this->getGitUrl(), $this->getTargetDirectory()]);
            $this->logger()->runProcess($process);
            // Ensure the cache directory is writeable.
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->getTargetDirectory() . "/storage/framework")
            );

            foreach ($iterator as $item) {
                chmod($item, 0777);
            }
            $this->installData()->set($this->getInstallConfig(), true);
            $this->tryToSetToSameBranch();
        }

        // It already exists.
        $this->installData()->set($this->getInstallConfig(), true);
        // Make sure we have the latest version of the queue.
        $this->logger()->title("Pulling latest version of '{$this->getName()}'");
        $process = new Process(["git", "pull"], $this->getTargetDirectory());
        try {
            $this->logger()->runProcess($process);
            $this->logger()->success("Latest '{$this->getName()}' Pulled");
        } catch (\Throwable $throwable) {
            $this->logger()->warning("'{$this->getName()}' did not pull successfully.");
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
     *
     * @return void
     */
    private function tryToSetToSameBranch()
    {
        $ownRepo = new Repository(PATH_ROOT);
        $targetRepo = new Repository($this->getTargetDirectory());

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
            "Found branch matching '{$ownBranch->getName()}' on '{$this->getName()}' do you want to switch to it?"
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
    private function composerInstall()
    {
        $this->logger()->title("{$this->getName()} - Composer Install");
        DockerUtils::composer($this->getTargetDirectory(), $this->getContainerName(), "/var/www/html", "install");
        DockerUtils::restartContainer($this->getTargetDirectory(), $this->getContainerName());
    }
}
