<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout\View;

use Garden\Container\Container;
use Garden\Hydrate\DataHydrator;
use Garden\Schema\Schema;
use Vanilla\Layout\Asset\AbstractLayoutAsset;
use Vanilla\Layout\Resolvers\ReactResolver;
use Vanilla\Web\PageHeadInterface;

/**
 * A layout view contains:
 *
 * - Param validation and expansion for that type.
 * - The assets provided for that view type.
 */
abstract class AbstractCustomLayoutView implements LayoutViewInterface
{
    /** @var array<class-string<AbstractLayoutAsset>> */
    private $assetClasses = [];

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Get LayoutView Type.
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * @return string
     */
    abstract public function getLayoutID(): string;

    /**
     * Get a schema for the input parameters when resolving this viewType.
     *
     * @return Schema
     */
    abstract public function getParamInputSchema(): Schema;

    /**
     * Get a schema for the resolved parameters that will be passed when hydrating the view.
     *
     * @return Schema
     */
    public function getParamResolvedSchema(): Schema
    {
        return new Schema();
    }

    /**
     * Given a set of parameters matching paramInputSchema, resolve them into paramResolve schema.
     *
     * @param array $paramInput The input parameters.
     *
     * @return array
     */
    public function resolveParams(array $paramInput, ?PageHeadInterface $pageHead = null): array
    {
        return $paramInput;
    }

    /**
     * Add an asset class.
     *
     * @param class-string<AbstractLayoutAsset> $assetClass
     */
    public function registerAssetClass(string $assetClass)
    {
        $this->assetClasses[] = $assetClass;
    }

    /**
     * Create a data hydrator with assets applied.
     *
     * @param DataHydrator $baseHydrator
     * @param Container $container
     * @return DataHydrator
     */
    public function createHydrator(DataHydrator $baseHydrator, Container $container): DataHydrator
    {
        $newHydrator = clone $baseHydrator;

        foreach ($this->assetClasses as $assetClass) {
            $newHydrator->addResolver(new ReactResolver($assetClass, $container));
        }

        return $newHydrator;
    }

    /**
     * Get all the registered asset classes
     *
     * @return array<class-string<AbstractLayoutAsset>>
     */
    public function getAssetClasses(): array
    {
        return $this->assetClasses;
    }
}
