<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Site\DefaultSiteSection;

/**
 * Mock site-section-provider.
 */
class MockSiteSectionProvider implements SiteSectionProviderInterface {

    /**
     * @var SiteSectionInterface[] $siteSections
     */
    private $siteSections = [];

    /** @var SiteSectionInterface */
    private $currentSiteSection;

    /**
     * MockSiteSectionProvider constructor.
     *
     * @param DefaultSiteSection $defaultSiteSection
     */
    public function __construct(DefaultSiteSection $defaultSiteSection) {
        $this->siteSections = array_merge([$defaultSiteSection]);
        $this->currentSiteSection = $defaultSiteSection;
    }

    /**
     * @param SiteSectionInterface[] $siteSections
     */
    public function addSiteSections(array $siteSections) {
        $this->siteSections = array_merge($this->siteSections, $siteSections);
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
        foreach ($this->siteSections as $section) {
            if ($section->getSectionID() === $id) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface {
        foreach ($this->siteSections as $section) {
            if ($section->getBasePath() === $basePath) {
                return $section;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getForLocale(string $localeKey): array {
        $sections = [];

        /** @var SiteSectionInterface $section */
        foreach ($this->siteSections as $section) {
            if ($section->getContentLocale() === $localeKey) {
                $sections[] = $section;
            }
        }

        return $sections;
    }

    /**
     * @inheritdoc
     */
    public function getCurrentSiteSection(): SiteSectionInterface {
        return $this->currentSiteSection;
    }

    /**
     * @param SiteSectionInterface $currentSiteSection
     */
    public function setCurrentSiteSection(SiteSectionInterface $currentSiteSection): void {
        $this->currentSiteSection = $currentSiteSection;
    }

    /**
     * Create site-sections to a section group.
     *
     * @param array $locales
     *
     * @return MockSiteSectionProvider
     */
    public static function fromLocales(array $locales = ["en", "fr", "es", "ru"]): MockSiteSectionProvider {
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

        /** @var MockSiteSectionProvider $provider */
        $provider = \Gdn::getContainer()->get(MockSiteSectionProvider::class);
        $provider->addSiteSections($siteSections);
        return $provider;
    }
}
