<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Contracts\Site;

use Garden\Http\HttpClient;
use Garden\Sites\Clients\SiteHttpClient;
use Garden\Sites\Cluster;
use Garden\Sites\SiteProvider;
use Garden\Sites\SiteRecord;
use Garden\Utils\ContextException;
use Vanilla\Navigation\Breadcrumb;

/**
 * Class representing a type.
 *
 * @extends \Garden\Sites\Site<VanillaSite, Cluster>
 */
class VanillaSite extends \Garden\Sites\Site implements \JsonSerializable
{
    protected string $name;
    protected ?SiteHttpClient $httpClient = null;

    /**
     * @param string $name
     * @param SiteRecord $siteRecord
     * @param SiteProvider $vanillaSiteProvider
     */
    public function __construct(string $name, SiteRecord $siteRecord, SiteProvider $vanillaSiteProvider)
    {
        parent::__construct($siteRecord, $vanillaSiteProvider);
        $this->name = $name;
    }

    /**
     * @param SiteProvider $siteProvider
     */
    public function setSiteProvider(SiteProvider $siteProvider): void
    {
        $this->siteProvider = $siteProvider;
    }

    /**
     * @param SiteHttpClient|null $httpClient
     * @return void
     */
    public function setHttpClient(?SiteHttpClient $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @return SiteRecord
     */
    public function getSiteRecord(): SiteRecord
    {
        return $this->siteRecord;
    }

    /**
     * Get the visual name of the site.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the web based URL root of the site.
     *
     * @return string
     */
    public function getWebUrl(): string
    {
        return $this->getBaseUrl();
    }

    /**
     * Get breadcrumbs representing the site.
     *
     * @return Breadcrumb[]
     */
    public function toBreadcrumbs(): array
    {
        return [new Breadcrumb($this->getName(), $this->getWebUrl())];
    }

    /**
     * @return SiteHttpClient
     */
    public function httpClient(): SiteHttpClient
    {
        return $this->httpClient ?? parent::httpClient();
    }

    /**
     * Get an authenticated HTTP client for the site.
     *
     * @return HttpClient
     */
    public function getHttpClient(): HttpClient
    {
        return $this->httpClient();
    }

    /**
     * @return array
     * @throws ContextException
     *
     * @deprecated
     */
    protected function loadSiteConfig(): array
    {
        // Config loading not implemented.
        return [];
    }
}
