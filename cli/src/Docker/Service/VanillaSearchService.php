<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Cli\Docker\Service;

use Webmozart\PathUtil\Path;

/**
 * Service for search.vanilla.local
 */
class VanillaSearchService extends AbstractLaravelService
{
    const SERVICE_ID = "search";

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(
            new LaravelServiceDescriptor(
                serviceID: self::SERVICE_ID,
                label: "Search Service",
                containerName: "vanilla-search",
                url: "https://search.vanilla.local",
                gitUrl: "git@github.com:vanilla/vanilla-search-service.git"
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function getVanillaConfigDefaults(): array
    {
        return [
            "ElasticDev.Secret" => "localhostsecret",
            "Inf.SearchApi.URL" => "http://search.vanilla.local",
            "EnabledPlugins.ElasticSearch" => true,
        ];
    }
}
