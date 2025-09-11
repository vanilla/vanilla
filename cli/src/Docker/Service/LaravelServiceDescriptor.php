<?php
/**
 * @author Adam Charron <acharron@higherlogic.com>
 * @copyright 2009-2025 Higher Logic LLC
 * @license GPL-2.0-only
 */

namespace Vanilla\Cli\Docker\Service;

class LaravelServiceDescriptor extends ServiceDescriptor
{
    /**
     * @param string $serviceID Identifier used for the service. Use in the CLI commands. Eg vnla docker up --service $serviceID
     * @param string $label Human-readable name for the service. Eg "Vanilla Files"
     * @param string $containerName The "container_name" of the primary docker container for the service.
     * @param string|string[] $url URL where the service is available. Eg "https://files.vanilla.local"
     * @param string $gitUrl URL to the git repository for the service. Eg "git@github.com:vanilla/vnla-jobber.git"
     */
    public function __construct(
        public string $serviceID,
        public string $label,
        public string $containerName,
        public string|array $url,
        public string $gitUrl
    ) {
        parent::__construct($serviceID, $label, $containerName, $url);
    }
}
