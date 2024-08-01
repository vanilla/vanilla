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
 * Service for mail.vanilla.localhost
 */
class VanillaMailhogService extends AbstractService
{
    const SERVICE_NAME = "mailhog";

    /**
     * @inheritDoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.mailhog.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "Mailhog";
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
        return "mail.vanilla.localhost";
    }

    /**
     * @inheritDoc
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
