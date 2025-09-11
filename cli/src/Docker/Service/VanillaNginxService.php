<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Commands\DockerCommand;

/**
 * Service for nginx.
 */
class VanillaNginxService extends AbstractService
{
    const SERVICE_ID = "nginx";

    protected bool $needsNginxReload = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Nginx",
                containerName: "nginx",
                url: "https://nginx.vanilla.local/health"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.nginx.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }
}
