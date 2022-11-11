<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Layout;

use Vanilla\Layout\Providers\LayoutProviderInterface;
use Vanilla\Layout\View\LayoutViewInterface;
use Vanilla\Layout\View\LegacyLayoutViewInterface;

/**
 * Used to mediate access to objects that can provide layouts
 */
class LayoutService
{
    //region Properties
    /** @var LayoutProviderInterface[] $layoutProviders  */
    private $layoutProviders;

    /** @var LayoutViewInterface[] */
    private $layoutViews;

    //endregion

    //region Constructor
    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->layoutProviders = [];
        $this->layoutViews = [];
    }
    //endregion

    //region Public Methods
    /**
     * Add/register a layout provider.
     *
     * @param LayoutProviderInterface $provider
     */
    public function addProvider(LayoutProviderInterface $provider): void
    {
        $this->layoutProviders[] = $provider;
    }

    /**
     * Add/register a layout view.
     *
     * @param LayoutViewInterface $view
     */
    public function addLayoutView(LayoutViewInterface $view): void
    {
        $this->layoutViews[] = $view;
    }

    /**
     * Get all registered layout providers
     *
     * @return array|LayoutProviderInterface[]
     */
    public function getProviders(): array
    {
        return $this->layoutProviders;
    }

    /**
     * Get a layout provider that is compatible with the ID provided given its type and value.
     *
     * @param int|string $layoutID ID of layout for which to retrieve a layout provider
     * @return LayoutProviderInterface|null
     */
    public function getCompatibleProvider($layoutID): ?LayoutProviderInterface
    {
        foreach ($this->layoutProviders as $layoutProvider) {
            if ($layoutProvider->isIDFormatSupported($layoutID)) {
                return $layoutProvider;
            }
        }
        return null;
    }

    /**
     * Get all layout views.
     *
     * @return LayoutViewInterface[]
     */
    public function getLayoutViews(): array
    {
        return $this->layoutViews;
    }

    /**
     * Get all registered legacy layout views.
     *
     * @return LegacyLayoutViewInterface[]
     */
    public function getLegacyLayoutViews(): array
    {
        $legacyViews = [];
        foreach ($this->getLayoutViews() as $view) {
            if ($view instanceof LegacyLayoutViewInterface) {
                $legacyViews[] = $view;
            }
        }

        return $legacyViews;
    }

    /**
     * @return string[]
     */
    public function getLayoutViewTypes(): array
    {
        $result = [];
        foreach ($this->getLayoutViews() as $layoutView) {
            $result[] = $layoutView->getType();
        }
        return $result;
    }

    //endregion
}
