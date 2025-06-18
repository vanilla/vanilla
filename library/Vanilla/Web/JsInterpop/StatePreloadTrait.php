<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\JsInterpop;

/**
 * Trait for adding preloaded redux actions to a controller.
 */
trait StatePreloadTrait
{
    /** @var ReduxAction[] */
    private array $reduxActions = [];

    /** @var ReduxActionProviderInterface[] */
    private array $actionProviders = [];

    /** @var array<array{array, mixed}>  */
    private array $reactQuerys = [];

    /**
     * Register redux action preloader or react query preloader.
     *
     * @param ReduxActionProviderInterface|ReactQueryPreloadProvider $provider The provider to register.
     */
    public function registerPreloader(ReduxActionProviderInterface|ReactQueryPreloadProvider $provider): void
    {
        $this->actionProviders[] = $provider;
    }

    /**
     * Add a redux action for the frontend to handle.
     *
     * @param ReduxAction $action The action to add.
     *
     * @return $this Own instance for chaining.
     */
    public function addReduxAction(ReduxAction $action): self
    {
        $this->reduxActions[] = $action;
        return $this;
    }

    /**
     * Preload a react query.
     *
     * @param PreloadedQuery $query
     *
     * @return $this Own instance for chaining.
     */
    public function preloadReactQuery(PreloadedQuery $query): self
    {
        $this->reactQuerys[] = $query;
        return $this;
    }

    /**
     * Get a stringable JS variable containing all preloaded redux actions.
     *
     * @return PhpAsJsVariable
     */
    protected function getReduxActionsAsJsVariable(): PhpAsJsVariable
    {
        // Apply all extra providers.
        foreach ($this->actionProviders as $provider) {
            try {
                if ($provider instanceof ReactQueryPreloadProvider) {
                    $this->reactQuerys = array_merge($this->reactQuerys, $provider->createQueries());
                } else {
                    $this->reduxActions = array_merge($this->reduxActions, $provider->createActions());
                }
            } catch (\Exception $e) {
                $this->reduxActions[] = new ReduxErrorAction($e);
            }
        }

        return new PhpAsJsVariable([
            "__ACTIONS__" => $this->reduxActions,
            "__REACT_QUERY_PRELOAD__" => $this->reactQuerys,
        ]);
    }
}
