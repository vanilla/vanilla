<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Site;

use Garden\Schema\Schema;
use Gdn;
use Gdn_Router;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Contracts\Site\SiteSectionChildIDProviderInterface;
use Vanilla\Contracts\Site\SiteSectionCounterInterface;
use Vanilla\Contracts\Site\SiteSectionCountStasherInterface;
use Vanilla\Contracts\Site\SiteSectionInterface;
use Vanilla\Contracts\Site\SiteSectionProviderInterface;
use Vanilla\Forms\FieldMatchConditional;
use VanillaTests\Fixtures\MockSiteSectionProvider;

/**
 * Class SiteSectionModel
 * @package Vanilla\Site
 */
class SiteSectionModel implements SiteSectionChildIDProviderInterface
{
    /** @var SiteSectionProviderInterface[] $providers */
    private $providers = [];

    /** @var SiteSectionCounterInterface[] */
    private $siteSectionCounters = [];

    /** @var SiteSectionChildIDProviderInterface[] */
    private $siteSectionChildIDProviders = [];

    /** @var SiteSectionInterface[] $siteSections */
    private $siteSections;

    /** @var SiteSectionInterface $currentSiteSection */
    private $currentSiteSection;

    /** @var SiteSectionInterface $defaultSiteSection */
    private $defaultSiteSection;

    /** @var array $defaultRoutes */
    private $defaultRoutes = [];

    /** @var Application[] $apps */
    private $apps = [];

    /** @var SiteSectionInterface[] $siteSectionsForAttribute */
    private $siteSectionsForAttributes = [];

    private \Gdn_Request $request;

    /**
     * SiteSectionModel constructor.
     */
    public function __construct(ConfigurationInterface $config, Gdn_Router $router, \Gdn_Request $request)
    {
        $this->defaultSiteSection = new DefaultSiteSection($config, $router);
        $this->request = $request;
    }

    /**
     * Register site section
     *
     * @param SiteSectionProviderInterface $provider
     */
    public function addProvider(SiteSectionProviderInterface $provider)
    {
        foreach ($this->providers as $existingProvider) {
            if (
                get_class($provider) === get_class($existingProvider) &&
                !($provider instanceof MockSiteSectionProvider)
            ) {
                return;
            }
        }
        $this->providers[] = $provider;
        $this->siteSections = null;
        if (!empty(($current = $provider->getCurrentSiteSection()))) {
            $this->currentSiteSection = $current;
        }
    }

    /**
     * Register a counter.
     *
     * @param SiteSectionCounterInterface $counter
     */
    public function addCounter(SiteSectionCounterInterface $counter)
    {
        foreach ($this->siteSectionCounters as $existingCounter) {
            if (get_class($counter) === get_class($existingCounter)) {
                return;
            }
        }

        $this->siteSectionCounters[] = $counter;
    }

    /**
     * Register an ID provider.
     *
     * @param SiteSectionChildIDProviderInterface $idProvider
     */
    public function addChildIDProvider(SiteSectionChildIDProviderInterface $idProvider)
    {
        foreach ($this->siteSectionChildIDProviders as $existingCounter) {
            if (get_class($idProvider) === get_class($existingCounter)) {
                return;
            }
        }

        $this->siteSectionChildIDProviders[] = $idProvider;
    }

    /**
     * Register optional default route
     *
     * @param string $name
     * @param array $route Array should contain Destination and Type.
     *          eg: ['Destination' => 'discussions', 'Type' => 'Internal', 'ImageUrl' => 'layout.png']
     */
    public function addDefaultRoute(string $name, array $route)
    {
        $this->defaultRoutes[$name] = $route;
    }

    /**
     * Get default route options
     *
     * @return array
     */
    public function getDefaultRoutes(): array
    {
        return $this->defaultRoutes;
    }

    /**
     * Get layout options
     *
     * @return array
     */
    public function getLayoutOptions(): array
    {
        $layouts = [
            "discussions" => "Discussions",
            "categories" => "Categories",
        ];
        foreach ($this->defaultRoutes as $name => $route) {
            $layouts[$route["Destination"]] = $name;
        }
        return $layouts;
    }
    /**
     * Get all site sections that match a particular site section group.
     *
     * @param string $sectionGroupKey The name of the section group to check.
     * @return SiteSectionInterface[]
     */
    public function getForSectionGroup(string $sectionGroupKey): array
    {
        $siteSections = [];
        foreach ($this->getAll() as $siteSection) {
            if ($siteSection->getSectionGroup() === $sectionGroupKey) {
                $siteSections[] = $siteSection;
            }
        }
        return $siteSections;
    }

    /**
     * Get a site section by from its ID.
     *
     * @param string $siteSectionID
     * @param string|null $locale
     * @return SiteSectionInterface|null
     */
    public function getByID(string $siteSectionID, ?string $locale = null): ?SiteSectionInterface
    {
        if ($locale) {
            $siteSectionID .= "-$locale";
        }

        foreach ($this->getAll() as $siteSection) {
            if ($siteSection->getSectionID() === $siteSectionID) {
                return $siteSection;
            }
        }
        return null;
    }

    /**
     * Returns all sections of the site.
     *
     * @return SiteSectionInterface[]
     */
    public function getAll(): array
    {
        if (empty($this->siteSections)) {
            $this->siteSections = [];
            foreach ($this->providers as $provider) {
                $this->siteSections = array_merge($this->siteSections, $provider->getAll());
            }

            // Make sure we have only unique values.
            $uniqueSections = [];
            foreach ($this->siteSections as $siteSection) {
                if (!isset($uniqueSections[$siteSection->getSectionID()])) {
                    $uniqueSections[$siteSection->getSectionID()] = $siteSection;
                }
            }
            $this->siteSections = array_values($uniqueSections);
        }
        return $this->siteSections;
    }

    /**
     * Get a site section from its base path.
     *
     * @param string $basePath
     * @return SiteSectionInterface|null
     */
    public function getByBasePath(string $basePath): ?SiteSectionInterface
    {
        /** @var SiteSectionInterface $siteSection */
        foreach ($this->getAll() as $siteSection) {
            if ($siteSection->getBasePath() === $basePath) {
                return $siteSection;
            }
        }
        return null;
    }

    /**
     * Get all site sections that match a particular locale.
     *
     * @param string $localeKey The locale key to lookup by.
     * @return SiteSectionInterface[]
     */
    public function getForLocale(string $localeKey): array
    {
        $siteSections = [];
        /** @var SiteSectionInterface $siteSection */
        foreach ($this->getAll() as $siteSection) {
            if ($localeKey === $siteSection->getContentLocale()) {
                $siteSections[] = $siteSection;
            }
        }
        return $siteSections;
    }

    /**
     * Get the current site section for the request automatically if possible.
     *
     * @return SiteSectionInterface
     */
    public function getCurrentSiteSection(): SiteSectionInterface
    {
        if (is_null($this->currentSiteSection)) {
            foreach ($this->providers as $provider) {
                if (!empty(($current = $provider->getCurrentSiteSection()))) {
                    $this->currentSiteSection = $current;
                }
            }
        }
        return $this->currentSiteSection ?? $this->defaultSiteSection;
    }

    /**
     * Get information about the current site section that applies to all layout queries.
     *
     * @return array
     */
    public function getCurrentLayoutParams(): array
    {
        $siteSection = SiteSectionSchema::toArray($this->getCurrentSiteSection());
        return [
            "locale" => $siteSection["contentLocale"],
            "siteSectionID" => $siteSection["sectionID"],
        ];
    }

    /**
     * Reset the current site section.
     */
    public function resetCurrentSiteSection()
    {
        $this->currentSiteSection = null;
    }

    /**
     * @param SiteSectionInterface $currentSiteSection
     */
    public function setCurrentSiteSection(SiteSectionInterface $currentSiteSection): void
    {
        $this->currentSiteSection = $currentSiteSection;
    }

    /**
     * Register application available
     *
     * @param string $app
     * @param array $settings
     */
    public function registerApplication(string $app, array $settings)
    {
        $this->apps[$app] = $settings;
    }

    /**
     * Get all available applications
     *
     */
    public function applications(): array
    {
        return $this->apps;
    }

    /**
     * Get a site-section by it's attribute name and value.
     *
     * @param string $attributeName
     * @param string|int $attributeValue
     *
     * @return SiteSectionInterface
     */
    public function getSiteSectionForAttribute(string $attributeName, $attributeValue): SiteSectionInterface
    {
        $key = "siteSection" . "_" . $attributeName . "_" . $attributeValue;
        $result = $this->siteSectionsForAttributes[$key] ?? null;

        if (!$result) {
            foreach ($this->getAll() as $siteSection) {
                // Lookup and find the attribute.

                $attributes = $siteSection->getAttributes();
                $attribute = $attributes[$attributeName] ?? [];
                if (is_array($attribute)) {
                    if (in_array($attributeValue, $attribute)) {
                        $result = $siteSection;
                        break;
                    }
                } elseif ($attribute === $attributeValue) {
                    $result = $siteSection;
                    break;
                }
            }

            $this->siteSectionsForAttributes[$key] = $result;
        }

        // If we still don't have a siteSection.
        if (!$result) {
            $this->siteSectionsForAttributes[$key] = $this->getDefaultSiteSection();
        }

        return $this->siteSectionsForAttributes[$key];
    }

    /**
     * Get the canonical site sections for a category. There could be multiple or none.
     *
     * @param int $categoryID
     *
     * @return SiteSectionInterface[]
     */
    public function getSiteSectionsForCategory(int $categoryID): array
    {
        $siteSections = [];
        foreach ($this->getAll() as $siteSection) {
            $categories = $siteSection->getAttributes()["allCategories"] ?? [];
            if (in_array($categoryID, $categories)) {
                $siteSections[] = $siteSection;
            }
        }
        return $siteSections;
    }

    /**
     * @return SiteSectionInterface
     */
    public function getDefaultSiteSection(): SiteSectionInterface
    {
        $default = null;
        foreach ($this->providers as $provider) {
            if (!empty(($current = $provider->getDefaultSiteSection()))) {
                $default = $current;
            }
        }
        return $default ?? $this->defaultSiteSection;
    }

    /**
     * Recalculate counts for a site section.
     *
     * @param SiteSectionInterface $siteSection
     *
     * @return array The counts.
     */
    public function recalculateCounts(SiteSectionInterface $siteSection): array
    {
        $stashers = $this->getCountStashers();
        if (empty($stashers)) {
            // Don't calculate counts if nothing is saving them.
            return [];
        }

        $counts = [];
        foreach ($this->siteSectionCounters as $siteSectionCounter) {
            $counts = array_merge($counts, $siteSectionCounter->calculateCountsForSiteSection($this, $siteSection));
        }

        foreach ($stashers as $stasher) {
            $stasher->stashCountsForSiteSection($siteSection, $counts);
        }
        return $counts;
    }

    /**
     * @return SiteSectionCountStasherInterface[]
     */
    private function getCountStashers(): array
    {
        $stashers = [];
        foreach ($this->providers as $siteSectionProvider) {
            if ($siteSectionProvider instanceof SiteSectionCountStasherInterface) {
                $stashers[] = $siteSectionProvider;
            }
        }

        return $stashers;
    }

    /**
     * Clear the local cache of site sections.
     */
    public function clearLocalCache()
    {
        $this->siteSections = [];
        $this->siteSectionsForAttributes = [];
    }

    /**
     * @inheritdoc
     */
    public function getChildIDs(SiteSectionInterface $siteSection): array
    {
        $result = [];
        foreach ($this->siteSectionChildIDProviders as $siteSectionProvider) {
            $childIDs = $siteSectionProvider->getChildIDs($siteSection);
            if (!empty($childIDs)) {
                $result = array_merge_recursive($childIDs, $result);
            }
        }

        return $result;
    }

    /**
     * Get a schema for a site section picker.
     *
     * @param FieldMatchConditional|null $conditional
     *
     * @return Schema|null
     */
    public function getSiteSectionFormOption(?FieldMatchConditional $conditional): ?Schema
    {
        $options = null;
        foreach ($this->providers as $provider) {
            $options = $options ?? $provider->getSiteSectionIDSchema($conditional);
        }
        return $options;
    }
}
