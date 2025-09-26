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
    const SERVICE_ID = "queue";

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new LaravelServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Queue Service",
                containerName: "vanilla-queue",
                url: "https://queue.vanilla.local",
                gitUrl: "git@github.com:vanilla/vanilla-queue-service.git"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "EnabledPlugins.vanilla-queue" => true,
            "VanillaQueue.BaseUrl" => "http://queue.vanilla.local",
        ];
    }
}
