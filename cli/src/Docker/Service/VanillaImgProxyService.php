<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Commands\DockerCommand;

/**
 * Service for imgproxy.vanilla.localhost
 */
class VanillaImgProxyService extends AbstractService
{
    const SERVICE_NAME = "imgproxy";

    const KEY = "dummykey";
    const SALT = "dummysalt";

    /**
     * @inheritDoc
     */
    public function getEnv(): array
    {
        return [
            "COMPOSE_FILE" => "./docker-compose.imgproxy.yml",
            "COMPOSE_IGNORE_ORPHANS" => true,
            "IMGPROXY_KEY" => $this->strToHex(self::KEY),
            "IMGPROXY_SALT" => $this->strToHex(self::SALT),
        ];
    }

    /**
     * @param string $string
     * @return string
     */
    private function strToHex(string $string)
    {
        $hexStr = unpack("H*", $string);
        return array_shift($hexStr);
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return "ImgProxy";
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
        return "imgproxy.vanilla.localhost";
    }

    /**
     * @inheritDoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "Plugins.ImageProxy.Key" => self::KEY,
            "Plugins.ImageProxy.Salt" => self::SALT,
            "Plugins.ImageProxy.Url" => "https://imgproxy.vanilla.localhost",
        ];
    }
}
