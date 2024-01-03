<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Webmozart\PathUtil\Path;

/**
 * Service for search.vanilla.localhost
 */
class VanillaSearchService extends AbstractLaravelService
{
    const SERVICE_NAME = "search";

    function getName(): string
    {
        return "vanilla-search-service";
    }

    function getTargetDirectory(): string
    {
        return Path::canonicalize(PATH_ROOT . "/../vanilla-search-service");
    }

    function getGitUrl(): string
    {
        return "git@github.com:vanilla/vanilla-search-service.git";
    }

    function getInstallConfig(): string
    {
        return "docker.wasSearchCloned";
    }

    function getHostname(): string
    {
        return "search.vanilla.localhost";
    }

    function getContainerName(): string
    {
        return "vanilla-search";
    }

    public function getVanillaConfigDefaults(): array
    {
        return [
            "ElasticDev.Secret" => "localhostsecret",
            "Inf.SearchApi.URL" => "http://search.vanilla.localhost",
            "EnabledPlugins.ElasticSearch" => true,
        ];
    }
}
