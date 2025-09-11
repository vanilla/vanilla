<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Garden\Web\Exception\HttpException;
use Vanilla\Cli\Commands\DockerCommand;
use Vanilla\Cli\Docker\ElasticSearchHttpClient;
use Vanilla\Cli\Docker\KibanaHttpClient;

/**
 * Service for mail.vanilla.local
 */
class VanillaMailhogService extends AbstractService
{
    const SERVICE_ID = "mailhog";

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Local Mail",
                containerName: "localmail",
                url: "https://mail.vanilla.local"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.mailhog.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "Garden.Email.Format" => "html",
            "Garden.Email.Hostname" => "localmail",
            "Garden.Email.SmtpHost" => "localmail",
            "Garden.Email.SmtpPort" => 1025,
            "Garden.Email.UseSmtp" => true,
        ];
    }
}
