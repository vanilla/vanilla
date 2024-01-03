<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Garden\Container\Container;
use Garden\Sites\Cluster;
use Garden\Sites\Mock\MockSiteProvider;
use Garden\Sites\SiteRecord;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\VanillaSite;
use Vanilla\Formatting\Html\HtmlPlainTextConverter;
use Vanilla\Http\InternalClient;
use Vanilla\Utility\ArrayUtils;
use VanillaTests\Fixtures\MockConfig;

/**
 * Baseline own site. Contents are entirely configuration based.
 *
 * - IDs are pulled from configuration.
 * - HttpClient is an `InternalClient` so requests don't actually go through HTTP.
 */
class OwnSite extends VanillaSite
{
    const CONF_ACCOUNT_ID = "Vanilla.AccountID";
    const CONF_SITE_ID = "Vanilla.SiteID";
    const CONF_CLUSTER_ID = "Vanilla.ClusterID";
    const CONF_CLUSTER_REGION_ID = "Vanilla.ClusterRegionID";

    protected ConfigurationInterface $config;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param HtmlPlainTextConverter $plainTextConverter
     * @param \Gdn_Request $request
     * @param Container $container
     */
    public function __construct(
        ConfigurationInterface $config,
        HtmlPlainTextConverter $plainTextConverter,
        \Gdn_Request $request,
        Container $container
    ) {
        $this->config = $config;
        $name = $plainTextConverter->convert($config->get("Garden.Title", ""));
        parent::__construct(
            $name,
            new SiteRecord(
                $config->get(self::CONF_SITE_ID, -1),
                $config->get(self::CONF_ACCOUNT_ID, -1),
                null,
                $config->get(self::CONF_CLUSTER_ID, "cl00000"),
                $request->getSimpleUrl("")
            ),
            new MockSiteProvider()
        );
        $ownSiteProvider = new OwnSiteProvider($this);
        $this->setSiteProvider($ownSiteProvider);
        $internalClient = new InternalClient($container, $this, "");
        $internalClient->setThrowExceptions(true);

        $this->setHttpClient($internalClient);
    }

    /**
     * @param int $siteID
     * @return void
     */
    public function setSiteID(int $siteID): void
    {
        $this->siteRecord = new SiteRecord(
            $siteID,
            $this->getAccountID(),
            null,
            $this->getClusterID(),
            $this->getBaseUrl()
        );
    }

    /**
     * @return Cluster
     */
    public function getCluster(): Cluster
    {
        return new Cluster(
            $this->getClusterID(),
            $this->config->get(self::CONF_CLUSTER_REGION_ID, Cluster::REGION_LOCALHOST)
        );
    }

    /**
     * Overridden to provide local site config.
     * @inheritDoc
     */
    protected function loadSiteConfig(): array
    {
        return $this->config->getAll();
    }
}
