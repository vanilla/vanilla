<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\Layout\GlobalRecordProvider;

/**
 * Site section definition for a site with only a single section.
 * This is a baseline for when no multisite provider is configured.
 */
class RootSiteSection implements SiteSectionInterface
{
    const DEFAULT_ID = 0;

    const DEFAULT_CATEGORY_ID = -2;

    const EMPTY_BASE_PATH = "";

    const DEFAULT_SECTION_GROUP = "vanilla";

    /** @var string */
    private $configSiteName;

    /** @var string|null */
    private $configSiteDescription;

    /** @var string */
    private $configLocaleKey;

    /** @var array $defaultRoute */
    private $defaultRoute;

    /** @var array $apps */
    private $apps;

    /**
     * @var string
     */
    private $bannerImageLink;

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     */
    public function __construct(ConfigurationInterface $config, \Gdn_Router $router)
    {
        $this->configSiteName = $config->get("Garden.Title", "Vanilla");
        $this->configSiteDescription = $config->get("Garden.Description", null);
        $this->configLocaleKey = $config->get("Garden.Locale", "en");
        $configDefaultController = $config->get("Routes.DefaultController");
        $this->defaultRoute = $router->parseRoute($configDefaultController);
        $this->apps = ["forum" => !(bool) $config->get("Vanilla.Forum.Disabled")];
        $this->bannerImageLink = $config->get(BannerImageModel::DEFAULT_CONFIG_KEY, "");
    }

    /**
     * @inheritdoc
     */
    public function getBasePath(): string
    {
        return self::EMPTY_BASE_PATH;
    }

    /**
     * @inheritdoc
     */
    public function getContentLocale(): string
    {
        return $this->configLocaleKey;
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string
    {
        return $this->configSiteName;
    }

    /**
     * @inheritDoc
     * @return string|null
     */
    public function getSectionDescription(): ?string
    {
        return $this->configSiteDescription;
    }

    /**
     * @inheritdoc
     */
    public function getSectionID(): string
    {
        return self::DEFAULT_ID;
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string
    {
        return self::DEFAULT_SECTION_GROUP;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return SiteSectionSchema::toArray($this);
    }

    /**
     * @inheritdoc
     */
    public function getDefaultRoute(): array
    {
        return $this->defaultRoute;
    }

    /**
     * @inheritdoc
     */
    public function applications(): array
    {
        return $this->apps;
    }

    /**
     * @inheritdoc
     */
    public function applicationEnabled(string $app): bool
    {
        return $this->apps[$app] ?? true;
    }

    /**
     * @inheritdoc
     */
    public function setApplication(string $app, bool $enable = true)
    {
        $this->apps[$app] = $enable;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        return [
            "categoryID" => self::DEFAULT_CATEGORY_ID,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getSectionThemeID()
    {
        return null;
    }

    /**
     * Get categoryID associated to site-section.
     *
     * @return int|null
     */
    public function getCategoryID()
    {
        return self::DEFAULT_CATEGORY_ID;
    }

    /**
     * @inheritDoc
     */
    public function getBannerImageLink(): string
    {
        return $this->bannerImageLink;
    }

    /**
     * @inheritDoc
     */
    public function getLayoutIdLookupParams(string $layoutViewType, string $recordType, string $recordID): array
    {
        return [
            "layoutViewType" => $layoutViewType,
            "recordType" => "root",
            "recordID" => self::DEFAULT_CATEGORY_ID,
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTrackablePayload(): array
    {
        return [
            "Subcommunity" => [
                "SubcommunityID" => $this->getSectionID(),
                "Locale" => $this->getContentLocale(),
                "Folder" => $this->getBasePath(),
                "Name" => $this->getSectionName(),
            ],
        ];
    }
}
