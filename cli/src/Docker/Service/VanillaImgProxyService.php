<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Vanilla\Cli\Commands\DockerCommand;

/**
 * Service for imgproxy.vanilla.local
 */
class VanillaImgProxyService extends AbstractService
{
    const SERVICE_ID = "imgproxy";

    const KEY = "dummykey";
    const SALT = "dummysalt";

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new ServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Image Proxy",
                containerName: "imgproxy",
                url: "https://imgproxy.vanilla.local"
            )
        );
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "Plugins.ImageProxy.Key" => self::KEY,
            "Plugins.ImageProxy.Salt" => self::SALT,
            "Plugins.ImageProxy.Url" => "https://imgproxy.vanilla.local",
        ];
    }
}
