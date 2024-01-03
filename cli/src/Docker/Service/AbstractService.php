<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\InstallDataTrait;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Commands\DockerCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

abstract class AbstractService
{
    use InstallDataTrait;
    use ScriptLoggerTrait;

    /**
     * Get a visual name of the service.
     */
    abstract public function getName(): string;

    /**
     * Get the directory of the services compose file.
     */
    abstract public function getTargetDirectory(): string;

    /**
     * Get the hostname the service binds to.
     */
    abstract public function getHostname(): string;

    /**
     * Get environment variables to start docker with.
     *
     * @return array
     */
    public function getEnv(): array
    {
        return [];
    }

    /**
     * Get some vanilla config defaults that should be applied when this config is enabled.
     *
     * @return array
     */
    public function getVanillaConfigDefaults(): array
    {
        return [];
    }

    /**
     * Start the service.
     *
     * @return void
     */
    public function start()
    {
        $this->startDocker();
        $this->reloadNginx();
        $this->finishStart();
    }

    /**
     * Stop the container.
     */
    public function stop()
    {
        $this->logger()->title("Stopping '{$this->getName()}'");
        DockerUtils::stopDocker($this->getTargetDirectory(), $this->getEnv());
    }

    /**
     * Start docker.
     */
    protected function startDocker()
    {
        $this->logger()->title("Starting '{$this->getName()}'");

        // Make sure we have an env file.
        $envFilePath = $this->getTargetDirectory() . "/.env";
        if (!file_exists($envFilePath)) {
            copy($this->getTargetDirectory() . "/.env.example", $envFilePath);
        }

        DockerUtils::startDocker(
            $this->getTargetDirectory(),
            ["VANILLA_LOCAL_CONFIG_DIR" => PATH_ROOT . "/conf"] + $this->getEnv()
        );
    }

    /**
     * Log that we've finished started.
     */
    protected function finishStart()
    {
        $this->logger()->info("{$this->getName()} is now running at <yellow>{$this->getHostname()}</yellow>");
    }

    /**
     * Reload the nginx container.
     */
    protected function reloadNginx()
    {
        DockerUtils::containerCommand(DockerCommand::VNLA_DOCKER_CWD, "nginx", "/", "nginx -s reload");
    }

    /**
     * Ensure that the repo is cloned and configured.
     */
    public function ensureCloned()
    {
    }
}
