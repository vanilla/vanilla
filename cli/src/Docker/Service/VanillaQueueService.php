<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Utils\DockerUtils;
use Webmozart\PathUtil\Path;

/**
 * Service for queue.vanilla.local
 */
class VanillaQueueService extends AbstractLaravelService
{
    const SERVICE_NAME = "queue";

    /**
     * @inheritDoc
     */
    public function start()
    {
        parent::start();
        $queueDir = $this->getTargetDirectory();
        if (!file_exists($queueDir . "/public/vendor/horizon")) {
            DockerUtils::artisan($queueDir, $this->getContainerName(), "/var/www/html", "horizon:publish");
        }
    }

    /**
     * @inheritDoc
     */
    function getName(): string
    {
        return "vanilla-queue-service";
    }

    /**
     * @inheritDoc
     */
    function getTargetDirectory(): string
    {
        return Path::canonicalize(PATH_ROOT . "/../vanilla-queue-service");
    }

    /**
     * @inheritDoc
     */
    function getGitUrl(): string
    {
        return "git@github.com:vanilla/vanilla-queue-service.git";
    }

    /**
     * @inheritDoc
     */
    function getInstallConfig(): string
    {
        return "docker.wasQueueCloned";
    }

    /**
     * @inheritDoc
     */
    function getHostname(): string
    {
        return "queue.vanilla.local";
    }

    /**
     * @inheritDoc
     */
    function getContainerName(): string
    {
        return "vanilla-queue";
    }

    /**
     * @inheritDoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "EnabledPlugins.vanilla-queue" => true,
            "VanillaQueue.BaseUrl" => "http://queue.vanilla.local",
        ];
    }
}
