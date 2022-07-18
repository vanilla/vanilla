<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
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
     * @return array
     */
    public function getHydrateParams(): array
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
        return ArrayUtils::getByPath($paramPath, $this->hydrateParams);
    }
}
