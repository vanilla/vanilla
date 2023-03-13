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
        $process->setIdleTimeout(60);
        $process->setTty(stream_isatty(STDOUT));
        $process->mustRun(function ($type, $data) {
            echo $data;
        });
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
        $process->setIdleTimeout(60);
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
        $process->setIdleTimeout(60);
        $process->setTty(stream_isatty(STDOUT));
        $process->mustRun(function ($type, $data) {
            echo $data;
        });
        return $process;
    }

    /**
     * Stop docker compose.
     *
     * @param string $cwd The root directory of the compose file.
     *
     * @return Process
     */
    public static function stopDocker(string $cwd)
    {
        $process = new Process(["docker", "compose", "down"], $cwd);
        $process->setTimeout(null);
        $process->setIdleTimeout(60);
        $process->setTty(stream_isatty(STDOUT));
        $process->mustRun(function ($type, $data) {
            echo $data;
        });
        return $process;
    }

    /**
     * Run composer in a container.
     *
     * @param string $cwd The CWD to run docker in.
     * @param string $containerName The name of the container to run composer in.
     * @param string $containerCwd Which directory inside the container to run composer in.
     * @param string $subCommand The composer subcommands to run.
     */
    public static function composer(string $cwd, string $containerName, string $containerCwd, string $subCommand)
    {
        $process = new Process(
            ["docker", "exec", "-it", $containerName, "sh", "-c", "cd $containerCwd && composer $subCommand"],
            $cwd
        );
        $process->setTimeout(null);
        $process->setIdleTimeout(null);
        $process->setTty(stream_isatty(STDOUT));
        $process->mustRun(function ($type, $data) {
            echo $data;
        });
        return $process;
    }
}
