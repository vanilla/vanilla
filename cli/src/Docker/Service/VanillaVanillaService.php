<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Symfony\Component\Process\Process;
use Vanilla\Cli\Commands\DockerCommand;
use Vanilla\Cli\Utils\DockerUtils;
use Vanilla\Cli\Utils\ShellUtils;

/**
 * Service for dev.vanilla.local
 */
class VanillaVanillaService extends AbstractService
{
    const SERVICE_NAME = "vanilla";

    /**
     * @inheritDoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.yml:./docker-compose.nginx.yml",
            "WWWGROUP" => getmygid(),
            "WWWUSER" => getmyuid(),
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Vanilla";
    }

    /**
     * @inheritDoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            // Typically captcha isn't configured on localhost.
            "Garden.Registration.SkipCaptcha" => true,

            // Bunch of feature flags to enable.
            "Feature.CommunityManagementBeta.Enabled" => true,
            "Feature.customLayout.discussionList.Enabled" => true,
            "Feature.customLayout.home.Enabled" => true,
            "Feature.customLayout.categoryList.Enabled" => true,
            "Feature.customLayout.post.Enabled" => true,
            "Feature.layoutEditor.discussionThread.Enabled" => true,
            "Feature.escalations.Enabled" => true,
            "Feature.CustomProfileFields.Enabled" => true,
            "Feature.discussionSiteMaps.Enable" => true,
            "Feature.NewUserManagement.Enabled" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTargetDirectory(): string
    {
        return DockerCommand::VNLA_DOCKER_CWD;
    }

    /**
     * @inheritDoc
     */
    public function getHostname(): string
    {
        return "dev.vanilla.local";
    }

    public function start()
    {
        parent::startDocker();
    }

    public function ensureCloned()
    {
        $this->ensureSymlinks();
    }

    /**
     * Ensure our config bootstrapping classes are symlinked.
     */
    private function ensureSymlinks()
    {
        $this->forceSymlink("../docker/bootstrap.before.php", PATH_CONF . "/bootstrap.before.php");
        $earlySource = "../docker/bootstrap.docker.php";
        $this->forceSymlink($earlySource, PATH_CONF . "/bootstrap.early.php");

        $cloudLinkScript = PATH_ROOT . "/cloud/scripts/symlink-addons";
        if (file_exists($cloudLinkScript)) {
            // Make sure our cloud addons are all symlinked.
            $process = new Process([$cloudLinkScript], PATH_ROOT);
            $process->mustRun();
        }
        $this->logger()->debug("Symlinks created");
    }

    /**
     * Create a symlink `ln -sf`
     *
     * @param string $symlinkTarget
     * @param string $symlinkFile
     */
    private function forceSymlink(string $symlinkTarget, string $symlinkFile)
    {
        if (file_exists($symlinkFile)) {
            unlink($symlinkFile);
        }
        $process = new Process(["ln", "-sf", $symlinkTarget, $symlinkFile], PATH_ROOT);
        $process->mustRun();
    }
}
