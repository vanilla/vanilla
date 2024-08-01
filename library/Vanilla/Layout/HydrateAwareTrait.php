<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Vanilla\Utility\ArrayUtils;

/**
 * Trait implementing {@link HydrateAwareInterface}
 */
trait HydrateAwareTrait
{
    /** @var array */
    private $hydrateParams;

    /** @var string[] */
    private $childComponentNames = [];

    /**
     * Set the parameters for the current hydration.
     *
     * @param array $params;
     *
     * @return void
     */
    public function setHydrateParams(array $params): void
    {
        $this->hydrateParams = $params;
    }

    /**
     * Get the resolved parameters for the current hydration.
     *
     * Params will have been resolved already using {@link LayoutHydrator}.
     *
     * @return array|null
     */
    public function getHydrateParams(): ?array
    {
        return $this->hydrateParams;
    }

    /**
     * Get a resolved parameter from the current hydration by an array path.
     *
     * Params will have been resolved already using {@link LayoutHydrator}.
     *
     * @param string $paramPath
     *
     * @return mixed
     */
    public function getHydrateParam(string $paramPath)
    {
        return ArrayUtils::getByPath($paramPath, $this->hydrateParams ?? []);
    }

    /**
     * Get names of additional child components that should be preloaded if this one is.
     *
     * @return array
     */
    public function getChildComponentNames(): array
    {
        return $this->childComponentNames;
    }

    /**
     * @param string $componentName
     */
    public function addChildComponentName(string $componentName): void
    {
        $this->childComponentNames[] = $componentName;
    }
}
