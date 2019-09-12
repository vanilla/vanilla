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
trait ReduxActionPreloadTrait {

    /** @var ReduxAction[] */
    private $reduxActions = [];

    /** @var ReduxActionProviderInterface[] */
    private $actionProviders = [];

    /**
     * Register an redux action preloader.
     *
     * @param ReduxActionProviderInterface $provider The provider to register.
     */
    public function registerReduxActionProvider(ReduxActionProviderInterface $provider) {
        $this->actionProviders[] = $provider;
    }

    /**
     * Add a redux action for the frontend to handle.
     *
     * @param ReduxAction $action The action to add.
     *
     * @return $this Own instance for chaining.
     */
    public function addReduxAction(ReduxAction $action): self {
        $this->reduxActions[] = $action;
        return $this;
    }

    /**
     * Get a stringable JS variable containing all preloaded redux actions.
     *
     * @return PhpAsJsVariable
     */
    protected function getReduxActionsAsJsVariable(): PhpAsJsVariable {
        // Apply all extra providers.
        foreach ($this->actionProviders as $provider) {
            $this->reduxActions = array_merge($this->reduxActions, $provider->createActions());
        }

        return new PhpAsJsVariable('__ACTIONS__', $this->reduxActions);
    }
}
