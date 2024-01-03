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
    const SERVICE_NAME = "nginx";

    /**
     * @inheritDoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.nginx.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Vanilla Nginx";
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
        return "*.vanilla.localhost";
    }

    /**
     * @inheritDoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [];
    }
}
