<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Layout\GlobalRecordProvider;
use Vanilla\Site\SiteSectionSchema;

/**
 * Mock site-section.
 */
class MockSiteSection implements SiteSectionInterface
{
    /** @var string */
    private $sectionID;

    /** @var string */
    private $siteSectionPath;

    /** @var string */
    private $sectionName;

    /** @var string */
    private $locale;

    /** @var string */
    private $sectionGroup;

    /** @var string|int */
    private $themeID;

    /** @var array $defaultRoute */
    private $defaultRoute;

    /** @var array $apps */
    private $apps;

    /**
     * @var string
     */
    private $bannerImageLink;

    /**
     * MockSiteSection constructor.
     *
     * @param string $sectionName
     * @param string $locale
     * @param string $basePath
     * @param string $sectionID
     * @param string $sectionGroup
     * @param array $defaultRoute
     * @param string $themeID
     * @param string $bannerImageLink
     */
    public function __construct(
        string $sectionName,
        string $locale,
        string $basePath,
        string $sectionID,
        string $sectionGroup,
        array $defaultRoute = [],
        string $themeID = null,
        string $bannerImageLink = ""
    ) {
        $this->sectionName = $sectionName;
        $this->locale = $locale;
        $this->siteSectionPath = $basePath;
        $this->sectionID = $sectionID;
        $this->sectionGroup = $sectionGroup;
        $this->defaultRoute = $defaultRoute;
        $this->apps = ["forum" => true];
        $this->themeID = $themeID;
        $this->bannerImageLink = $bannerImageLink;
    }
    /**
     * @inheritdoc
     */
    public function getBasePath(): string
    {
        return $this->siteSectionPath;
    }

    /**
     * @inheritdoc
     */
    public function getContentLocale(): string
    {
        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string
    {
        return $this->sectionName;
    }

    /**
     * @inheritDoc
     */
    public function getSectionDescription(): ?string
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSectionID(): string
    {
        return $this->sectionID;
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string
    {
        return $this->sectionGroup;
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
        return [];
    }

    /**
     * @return int|string|null
     */
    public function getSectionThemeID()
    {
        return $this->themeID;
    }

    /**
     * Get categoryID associated to site-section.
     *
     * @return int|null
     */
    public function getCategoryID()
    {
        return -1;
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
