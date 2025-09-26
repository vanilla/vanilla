<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Commands\DockerCommand;

/**
 * Service for mysql.
 */
class VanillaMySqlService extends AbstractService
{
    const SERVICE_ID = "mysql";

    protected bool $needsNginxReload = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "MySQL",
                containerName: "database",
                url: "mysql://mysql.vanilla.local:3306"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.database.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }
}
