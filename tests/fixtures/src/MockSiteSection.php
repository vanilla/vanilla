<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Site\SiteSectionSchema;

/**
 * Mock site-section.
 */
class MockSiteSection implements SiteSectionInterface {

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

    /**
     * MockSiteSection constructor.
     *
     * @param string $sectionName
     * @param string $locale
     * @param string $basePath
     * @param string $sectionID
     * @param string $sectionGroup
     */
    public function __construct(
        string $sectionName,
        string $locale,
        string $basePath,
        string $sectionID,
        string $sectionGroup
    ) {
        $this->sectionName = $sectionName;
        $this->locale = $locale;
        $this->siteSectionPath = $basePath;
        $this->sectionID = $sectionID;
        $this->sectionGroup = $sectionGroup;
    }
    /**
     * @inheritdoc
     */
    public function getBasePath(): string {
        return $this->siteSectionPath;
    }

    /**
     * @inheritdoc
     */
    public function getContentLocale(): string {
        return $this->locale;
    }

    /**
     * @inheritdoc
     */
    public function getSectionName(): string {
        return  $this->sectionName;
    }

    /**
     * @inheritdoc
     */
    public function getSectionID(): string {
        return $this->sectionID;
    }

    /**
     * @inheritdoc
     */
    public function getSectionGroup(): string {
        return $this->sectionGroup;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return SiteSectionSchema::toArray($this);
    }
}
