<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */


namespace VanillaTests\Fixtures;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;

/**
 * Mock site-section-provider.
 */
class MockSiteSectionProvider implements SiteSectionProviderInterface {

    /**
     * @var array $siteSections
     */
    private $siteSections = [];

    /**
     * MockSiteSectionProvider constructor.
     */
    public function __construct() {
        $this->siteSections = self::createSiteSections();
    }


    /**
     * @inheritdoc
     */
    public function getAll(): array{
        return $this->siteSections;
    }

    /**
     * @inheritdoc
     */
    public function getByID(int $id): ?SiteSectionInterface{

    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface{

    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {

    }

    /**
     * @inheritdoc
     */
    public function getCurrentSiteSection(): SiteSectionInterface{

    }

    /**
     * Create site-sections to a section group.

     * @return array
     */
    public static function createSiteSections() {
        $locales = ["en", "fr", "es", "ru"];
        $siteSections = [];
        foreach ($locales as $locale) {

            $siteSectionPath = $locale.'/';
            $siteSectionName = uniqid("siteSectionName");
            $sectionGroup = "subcommunities-group-1";
            $siteSectionID = "subcommunities-group-1";

            $siteSections[] = new MockSiteSection(
                $siteSectionName,
                $locale,
                $siteSectionPath,
                $siteSectionID,
                $sectionGroup
            );
        }

        return $siteSections;
    }

}
