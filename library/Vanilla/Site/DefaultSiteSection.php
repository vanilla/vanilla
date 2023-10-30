<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
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
class DefaultSiteSection implements SiteSectionInterface
{
    const DEFAULT_ID = 0;

    const DEFAULT_CATEGORY_ID = -1;

    const EMPTY_BASE_PATH = "";

    const DEFAULT_SECTION_GROUP = "vanilla";

    /** @var ConfigurationInterface */
    private $config;

    /** @var \Gdn_Router */
    private $router;

    /** @var array $appOverrides */
    private $appOverrides = [];

    /**
     * DI.
     *
     * @param ConfigurationInterface $config
     * @param \Gdn_Router $router
     */
    public function __construct(ConfigurationInterface $config, \Gdn_Router $router)
    {
        $this->config = $config;
        $this->router = $router;
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
        return $this->config->get("Garden.Locale", "en");
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string
    {
        return $this->config->get("Garden.Title", "Vanilla");
    }

    /**
     * @inheritDoc
     * @return string|null
     */
    public function getSectionDescription(): ?string
    {
        return $this->config->get("Garden.Description", null);
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
        $configDefaultController = $this->config->get("Routes.DefaultController");

        return $this->router->parseRoute($configDefaultController) + ["OverridesCustomLayout" => false];
    }

    /**
     * @inheritdoc
     */
    public function applications(): array
    {
        $apps = ["forum" => !(bool) $this->config->get("Vanilla.Forum.Disabled")];

        return array_merge($apps, $this->appOverrides);
    }

    /**
     * @inheritdoc
     */
    public function applicationEnabled(string $app): bool
    {
        return $this->applications()[$app] ?? true;
    }

    /**
     * @inheritdoc
     */
    public function setApplication(string $app, bool $enable = true)
    {
        $this->appOverrides[$app] = $enable;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        return [
            "categoryID" => -1,
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
        return $this->config->get(BannerImageModel::DEFAULT_CONFIG_KEY, "");
    }

    /**
     * @inheritDoc
     */
    public function getLayoutIdLookupParams(string $layoutViewType, string $recordType, string $recordID): array
    {
        return [
            "layoutViewType" => $layoutViewType,
            "recordType" => GlobalRecordProvider::getValidRecordTypes()[0],
            "recordID" => -1,
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
