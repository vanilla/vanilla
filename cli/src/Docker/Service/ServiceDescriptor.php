<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Docker\Service;

class ServiceDescriptor
{
    /**
     * @param string $serviceID Identifier used for the service. Use in the CLI commands. Eg vnla docker up --service $serviceID
     * @param string $label Human-readable name for the service. Eg "Vanilla Files"
     * @param string $containerName The "container_name" of the primary docker container for the service.
     * @param string|string[] $url URL or URLs where the service is available. Eg "https://files.vanilla.local"
     */
    public function __construct(
        public string $serviceID,
        public string $label,
        public string $containerName,
        public string|array $url
    ) {
    }

    /**
     * Get a formatted list of urls for display.
     *
     * @return string
     */
    public function formatUrls(): string
    {
        if (is_array($this->url)) {
            return implode(", ", $this->url);
        }
        return $this->url;
    }

    /**
     * Get all the hostnames for the service.
     *
     * @return array
     */
    public function getHostnames(): array
    {
        $urls = (array) $this->url;
        $hostnames = [];
        foreach ($urls as $url) {
            $hostname = parse_url($url, PHP_URL_HOST);
            if ($hostname) {
                $hostnames[] = $hostname;
            }
        }
        return $hostnames;
    }
}
