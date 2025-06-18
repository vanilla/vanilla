<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Exception;
use Garden\Schema\ValidationException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Vanilla\Cli\Commands\DockerContainerCommand;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\InstallDataTrait;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Commands\DockerCommand;
use Vanilla\Cli\Utils\ScriptLoggerTrait;
use Vanilla\EnvUtils;
use Vanilla\FileUtils;

/**
 * Class declaring a docker service for vnla docker.
 */
abstract class AbstractService
{
    use InstallDataTrait;
    use ScriptLoggerTrait;

    /** @var array<class-string<AbstractService>> */
    public static array $requiredServiceIDs = [];

    protected bool $needsNginxReload = true;

    public function __construct(public ServiceDescriptor $descriptor)
    {
    }

    /**
     * Get the directory of the services compose file.
     */
    public function getComposeFileDirectory(): string
    {
        return DockerCommand::VNLA_DOCKER_CWD;
    }

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
    public function start(): void
    {
        $this->startDocker();
        if ($this->needsNginxReload) {
            $this->reloadNginx();
        }
        $this->finishStart();
    }

    /**
     * Stop the container.
     */
    public function stop(): void
    {
        $this->logger()->title("Stopping '{$this->descriptor->label}'");
        DockerUtils::stopDocker($this->getComposeFileDirectory(), $this->getEnv());
    }

    /**
     * Start docker.
     */
    protected function startDocker(array $extraEnv = []): void
    {
        $this->logger()->title("Starting '{$this->descriptor->label}'");

        // Make sure we have an env file.
        $this->getEnvFileContents();

        DockerUtils::startDocker(
            $this->getComposeFileDirectory(),
            array_merge(["VANILLA_LOCAL_CONFIG_DIR" => PATH_ROOT . "/conf"], $this->getEnv(), $extraEnv)
        );
    }

    /**
     * @return array
     */
    public function getEnvFileContents(): array
    {
        $envFilePath = $this->getComposeFileDirectory() . "/.env";
        if (!file_exists($envFilePath)) {
            copy($this->getComposeFileDirectory() . "/.env.example", $envFilePath);
        }

        if (file_exists($envFilePath)) {
            $value = file_get_contents($envFilePath);
            $envVariables = EnvUtils::parseEnvFile($value);
            $this->validateEnvFile($envVariables);
        } else {
            $envVariables = [];
        }

        return $envVariables;
    }

    /**
     * Update the .env file for the service.
     *
     * @param array $updates
     * @return void
     *
     * @throws Exception If the env file does not exist.
     */
    public function updateEnvFile(array $updates): void
    {
        $envFilePath = $this->getComposeFileDirectory() . "/.env";

        if (!file_exists($envFilePath)) {
            throw new Exception("Env file does not exist at $envFilePath");
        }

        $existing = file_get_contents($envFilePath);
        $updates = EnvUtils::updateEnvFileContents($existing, $updates);
        file_put_contents($envFilePath, $updates);
        $this->logger()->info("Updated env file at $envFilePath");
    }

    /**
     * @param array $envVariables
     * @return void
     * @throws ValidationException
     */
    protected function validateEnvFile(array $envVariables): void
    {
        // nothing to do.
    }

    /**
     * Log that we've finished started.
     */
    protected function finishStart(): void
    {
        $this->logger()->info(
            "{$this->descriptor->label} is now running at <yellow>{$this->descriptor->formatUrls()}</yellow>"
        );
    }

    /**
     * Reload the nginx container.
     */
    protected function reloadNginx(): void
    {
        DockerUtils::containerCommand(DockerCommand::VNLA_DOCKER_CWD, "nginx", "/", "nginx -s reload");
    }

    /**
     * Ensure that the repo is cloned and configured.
     */
    public function ensureCloned(): void
    {
    }

    /**
     * Get container commands for the service.
     *
     * @return DockerContainerCommand[]
     */
    public function getContainerCommands(): array
    {
        return [new DockerContainerCommand($this)];
    }

    /**
     * Check if the service container is running.
     *
     * @return bool
     */
    public function isRunning(): bool
    {
        // First see if the container is running
        $process = new Process(["docker", "inspect", "-f", "{{.State.Running}}", $this->descriptor->containerName]);
        try {
            $process->mustRun();
            return true;
        } catch (ProcessFailedException $ex) {
            return false;
        }
    }

    /**
     * Ensure the service is running.
     *
     * @return void
     * @throws Exception
     */
    public function ensureRunning(): void
    {
        if (!$this->isRunning()) {
            throw new Exception(
                "Service {$this->descriptor->serviceID} is not running." .
                    "\nPlease start it with <yellow>'vnla docker:{$this->descriptor->serviceID} start'</yellow>",
                1
            );
        }
    }
}
