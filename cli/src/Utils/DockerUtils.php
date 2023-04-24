<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Utils;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Utilities for controlling docker.
 */
class DockerUtils
{
    /**
     * Validate that docker is installed.
     */
    public static function validateDocker()
    {
        $executableFinder = new ExecutableFinder();
        $dockerPath = $executableFinder->find("docker");
        if ($dockerPath === null) {
            throw new \Exception(
                "Could not find the 'docker' executable on your system. Did you forget to install it?"
            );
        }
        return $dockerPath;
    }

    /**
     * List all docker volumes.
     *
     * @return string[]
     */
    public static function listVolumes(): array
    {
        $process = new Process(["docker", "volume", "ls", "-q"]);
        $process->mustRun();
        $output = $process->getOutput();
        $volumeNames = explode("\n", $output);
        return $volumeNames;
    }

    /**
     * Check if a network exists.
     *
     * @param string $networkName
     *
     * @return bool
     */
    private static function networkExists(string $networkName): bool
    {
        $process = new Process(["docker", "network", "ls"]);
        $process->mustRun();
        $output = $process->getOutput();

        return str_contains($output, " $networkName ");
    }

    /**
     * Ensure the vanilla network is created.
     */
    public static function ensureVanillaNetwork(): void
    {
        if (self::networkExists("vanilla-network")) {
            return;
        }

        $process = new Process(["docker", "network", "create", "-d", "bridge", "vanilla-network"]);
        $process->mustRun();
    }

    /**
     * Copy the contents of one volume to another.
     *
     * @param string $fromVolume
     * @param string $toVolume
     */
    public static function copyVolumeContents(string $fromVolume, string $toVolume)
    {
        $process = new Process([
            "docker",
            "container",
            "run",
            "--rm",
            "-it",
            "-v=$fromVolume:/from",
            "-v=$toVolume:/to",
            "alpine",
            "ash",
            "-c",
            "cd /from ; cp -av . /to",
        ]);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        // Pretty noisy if we don't actually have a tty.
        $logger = new SimpleScriptLogger();
        $logger->runProcess($process);
        return $process;
    }

    /**
     * Restart a single container.
     *
     * @param string $cwd The root directory of the docker files.
     * @param string $containerName The name of the container to restart.
     */
    public static function restartContainer(string $cwd, string $containerName)
    {
        $process = new Process(["docker", "container", "restart", $containerName], $cwd);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->mustRun();
    }

    /**
     * Start docker compose.
     *
     * @param string $cwd The root directory of the compose file.
     *
     * @return Process
     */
    public static function startDocker(string $cwd, array $env = [])
    {
        $process = new Process(["docker", "compose", "up", "--build", "-d"], $cwd, $env);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $logger = new SimpleScriptLogger();
        $logger->runProcess($process, [DockerUtils::class, "filterDockerOutput"]);
        return $process;
    }

    /**
     * Stop docker compose.
     *
     * @param string $cwd The root directory of the compose file.
     * @param array $env Environment variables to set for the process.
     *
     * @return Process
     */
    public static function stopDocker(string $cwd, array $env = [])
    {
        $process = new Process(["docker", "compose", "down"], $cwd, $env);
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $logger = new SimpleScriptLogger();
        $logger->runProcess($process, [self::class, "filterDockerOutput"]);
        return $process;
    }

    /**
     * Filter output of docker for SimpleScriptLogger:runProcess().
     *
     * @param string $line
     * @return string|null
     */
    public static function filterDockerOutput(string $line): ?string
    {
        preg_match("/Container\s([\w-]+)\s+(.*)/", $line, $matches);
        if ($matches) {
            return "Container <yellow>{$matches[1]}</yellow> {$matches[2]}";
        } else {
            return null;
        }
    }

    /**
     * Run composer in a container.
     *
     * @param string $cwd The CWD to run docker in.
     * @param string $containerName The name of the container to run composer in.
     * @param string $containerCwd Which directory inside the container to run composer in.
     * @param string $subCommand The composer subcommands to run.
     *
     * @return Process
     */
    public static function composer(string $cwd, string $containerName, string $containerCwd, string $subCommand)
    {
        return self::containerCommand($cwd, $containerName, $containerCwd, "composer $subCommand");
    }

    /**
     * Run composer in a container.
     *
     * @param string $cwd The CWD to run docker in.
     * @param string $containerName The name of the container to run composer in.
     * @param string $containerCwd Which directory inside the container to run composer in.
     * @param string $subCommand The composer subcommands to run.
     *
     * @return Process
     */
    public static function artisan(string $cwd, string $containerName, string $containerCwd, string $subCommand)
    {
        return self::containerCommand($cwd, $containerName, $containerCwd, "php artisan $subCommand");
    }

    /**
     * Run composer in a container.
     *
     * @param string $cwd The CWD to run docker in.
     * @param string $containerName The name of the container to run composer in.
     * @param string $containerCwd Which directory inside the container to run composer in.
     * @param string $command The command to run.
     *
     * @return Process
     */
    public static function containerCommand(string $cwd, string $containerName, string $containerCwd, string $command)
    {
        $process = new Process(
            array_filter(["docker", "exec", $containerName, "sh", "-c", "cd $containerCwd && $command"]),
            $cwd
        );
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $logger = new SimpleScriptLogger();
        $logger->runProcess($process);
        return $process;
    }
}
