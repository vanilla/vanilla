<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */


namespace VanillaTests\fixtures;

use Vanilla\Contracts\Site\SiteSectionInterface;


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


    public function __construct(
        string $sectionName,
        string $locale,
        string $basePath,
        string $sectionID,
        string $sectionGroup) {

        $this->sectionName = $sectionName;
        $this->locale = $locale;
        $this->siteSectionPath = $basePath;
        $this->sectionID = $sectionID;
        $this->sectionGroup = $sectionGroup;
    }

    /**
     * @return string
     */
    public function getBasePath(): string {
        return $this->siteSectionPath;
    }

    /**
     * @return string
     */
    public function getContentLocale(): string {
        return $this->locale;
    }
    /**
     * @return string
     */
    public function getSectionName(): string{
        return  $this->sectionName;
    }

    /**
     * @return int
     */
    public function getSectionID(): int{
        return $this->sectionID;
    }

    /**
     * @return string
     */
    public function getSectionGroup(): string{
        return $this->sectionGroup;
    }


}
