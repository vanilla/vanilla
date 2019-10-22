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
     * @var SiteSectionInterface[] $siteSections
     */
    private $siteSections = [];

    /**
     * MockSiteSectionProvider constructor.
     */
    public function __construct() {
        $this->siteSections = self::fromLocales();
    }


    /**
     * @inheritdoc
     */
    public function getAll(): array {
        return $this->siteSections;
    }

    /**
     * @inheritdoc
     */
    public function getForSectionGroup(string $sectionGroupKey): array {
        $sections = [];

        /** @var SiteSectionInterface $section */
        foreach ($this->siteSections as $section) {
            if ($section->getSectionGroup() === $sectionGroupKey) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * @inheritdoc
     */
    public function getByID(int $id): ?SiteSectionInterface {
    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface {
    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {
    }

    /**
     * @inheritdoc
     */
    public function getCurrentSiteSection(): SiteSectionInterface {
        return $this->siteSections[0];
    }

    /**
     * Create site-sections to a section group.
     *
     * @param array $locales
     *
     * @return array
     */
    public static function fromLocales(array $locales = ["en", "fr", "es", "ru"]): array {

        $siteSections = [];

        foreach ($locales as $locale) {
            $siteSections[] = new MockSiteSection(
                "siteSectionName_".$locale,
                $locale,
                $locale.'/',
                "mockSiteSection-".$locale,
                "mockSiteSectionGroup-1"
            );

            $siteSections[] = new MockSiteSection(
                "ssg2_siteSectionName_".$locale,
                $locale,
                'ssg2-'.$locale.'/',
                "ssg2-mockSiteSection-".$locale,
                "mockSiteSectionGroup-2"
            );
        }

        return $siteSections;
    }
}
