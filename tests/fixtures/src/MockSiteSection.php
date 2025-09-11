<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Layout\GlobalLayoutRecordProvider;
use Vanilla\Site\SiteSectionSchema;

/**
 * Mock site-section.
 */
class MockSiteSection implements SiteSectionInterface
{
    /** @var array $apps */
    private $apps;

    /**
     * MockSiteSection constructor.
     *
     * @param string $sectionName
     * @param string $locale
     * @param string $siteSectionPath
     * @param string $sectionID
     * @param string $sectionGroup
     * @param array $defaultRoute
     * @param string|null $themeID
     * @param string $bannerImageLink
     * @param int $categoryID
     */
    public function __construct(
        private string $sectionName,
        private string $locale,
        private string $siteSectionPath,
        private string $sectionID,
        private string $sectionGroup,
        private array $defaultRoute = [],
        private ?string $themeID = null,
        private string $bannerImageLink = "",
        private int $categoryID = -1
    ) {
        $this->apps = ["forum" => true];
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
     * @inheritdoc
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
    public function jsonSerialize(): array
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
        return $this->categoryID;
    }

    /**
     * @inheritdoc
     */
    public function getBannerImageLink(): string
    {
        return $this->bannerImageLink;
    }

    /**
     * @inheritdoc
     */
    public function getLayoutRecordType(): string
    {
        return GlobalLayoutRecordProvider::RECORD_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function getLayoutRecordID(): int|string
    {
        return GlobalLayoutRecordProvider::RECORD_ID;
    }

    /**
     * @inheritdoc
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
