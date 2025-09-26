<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2025 Higher Logic.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Commands\DockerCommand;

/**
 * Service for files.vanilla.local & files-api.vanilla.local
 */
class VanillaFilesService extends AbstractService
{
    const SERVICE_ID = "files";

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Files API",
                containerName: "minio",
                url: ["https://files-api.vanilla.local", "https://files.vanilla.local"]
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.minio.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "Garden.Storage.Provider" => "awss3",
            "S3" => [
                "Endpoint" => "http://files-api.vanilla.local",
                "Region" => "us-west-2",
                "Credentials" => [
                    "Key" => "minio",
                    "Secret" => "password",
                ],
                "Prefix" => "default-bucket",
                "Zone" => "us",
            ],
        ];
    }
}
