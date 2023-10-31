<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Commands;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Vanilla\Cli\Utils\InstallDataTrait;
use Vanilla\Cli\Utils\ScriptLoggerTrait;

/**
 * Command to Publish base docker images.
 */
class DockerBuildCommand extends Console\Command\Command
{
    use ScriptLoggerTrait;
    use InstallDataTrait;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        parent::configure();
        $this->setName("docker-build")
            ->setDescription("Build and publish base docker images.")
            ->setDefinition(
                new Console\Input\InputDefinition([
                    new Console\Input\InputArgument(
                        "imagePattern",
                        Console\Input\InputArgument::OPTIONAL,
                        "Image Pattern",
                        "*"
                    ),
                    new Console\Input\InputOption(
                        "publish",
                        null,
                        null,
                        "Set this to publish the images after they are built.",
                        null
                    ),
                ])
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->ensureMultiarchBuilder();

        $this->logger()->title("Building Images");
        $rootDir = PATH_ROOT . "/docker/base-images";
        $pattern = $input->getArgument("imagePattern");
        $imageToDirName = [];

        // Loop through and find all the images we have
        // Match against our arg pattern if we have one.
        foreach (glob("$rootDir/*") as $imageDir) {
            $imageName = basename($imageDir);
            if (!fnmatch($pattern, $imageName)) {
                continue;
            }
            // This is a docker image.
            $path = $imageDir;
            $name = "vanillaforums/" . $imageName;
            $imageToDirName[$name] = $path;
        }

        $shouldPublish = (bool) $input->getOption("publish");
        foreach ($imageToDirName as $imageName => $dirName) {
            $extra = $shouldPublish ? " and Publishing " : " ";
            $this->logger()->info("Building{$extra}Image: <yellow>{$imageName}</yellow>");

            $buildCommand = [
                "docker",
                // Buildx for multi-arch
                "buildx",
                "--builder",
                "multiarch", // Ensured this existed with ensureMultiArchBuilder.
                "build",
                // Architechtures we are building for.
                "--platform",
                "linux/amd64,linux/arm64",
                "--tag",
                $imageName,
                $shouldPublish ? "--push" : null,
                ".",
            ];

            $process = new Process(array_filter($buildCommand), $dirName);
            $process->setTimeout(null);
            $process->setIdleTimeout(120);
            $this->logger()->runProcess($process, true);
        }

        return self::SUCCESS;
    }

    /**
     * Ensure we have a multi-architecture builder configured.
     */
    private function ensureMultiarchBuilder()
    {
        $process = new Process(["docker", "buildx", "ls"]);
        $this->logger()->runProcess($process);
        $hasMultiArchBuilder = str_contains($process->getOutput(), "multiarch");
        if (!$hasMultiArchBuilder) {
            $this->logger()->info("Creating new mutliarch builder.");
            $process = new Process(["docker", "buildx", "create", "--name", "mutliarch"]);
            $this->logger()->runProcess($process);
        }
    }
}
