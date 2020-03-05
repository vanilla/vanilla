/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Store, Reducer, AnyAction } from "redux";
import isEqual from "lodash/isEqual";

/**
 * Class for wrapping some backing redux store and making assertions about the dispatched actions.
 */
export class MockStore<GState> implements Store<GState, any> {
    private backingStore: Store<GState>;

    /**
     * @param backingStore A real store to back the mockStore.
     */
    public constructor(private createStore?: () => Store<GState>) {
        this.resetStore();
    }

    public resetStore() {
        if (this.createStore) {
            this.backingStore = this.createStore();
            this[Symbol.observable] = this.backingStore[Symbol.observable];
        }
    }

    ///
    /// Basic capturing & checking of of actions.
    ///
    private actions: AnyAction[] = [];

    /**
     * Dispatch an action & record it for inspection.
     *
     * @param action The action to dispatch
     */
    public dispatch = (action: any) => {
        this.actions.push(action);
        if (this.backingStore) {
            return this.backingStore.dispatch(action);
        } else {
            return action;
        }
    };

    /**
     * Get all dispatched actions.
     */
    public getActions() {
        return this.actions;
    }

    /**
     * Get the first matching action.
     *
     * @param type The type to check.
     */
    public getFirstActionOfType(type: string): AnyAction | null {
        const foundActions = this.actions.filter(action => action.type === type);
        return foundActions.length > 0 ? foundActions[0] : null;
    }

    /**
     * Check if an action of a certain type was dispatched.
     *
     * @param type The type to check.
     */
    public isActionTypeDispatched(type: string): boolean {
        const foundActions = this.actions.filter(action => action.type === type);
        return foundActions.length > 0;
    }

    /**
     * Check if an action was dispatched.
     *
     * @param actionToCheck The action to check for. An exact match must be found.
     */
    public isActionDispatched(actionToCheck: AnyAction) {
        const foundActions = this.actions.filter(action => isEqual(action, actionToCheck));
        return foundActions.length > 0;
    }

    ///
    /// Required to comply with interface
    /// Many of these proxy to the backingStore when possible.
    ///

    // Stubbed out & not needed for this implementation.
    [Symbol.observable]: any;

    /**
     * Get the state of the store.
     */
    public getState = (): GState => {
        this.requiresBackingStore("getState");
        return this.backingStore!.getState();
    };

    /**
     * @inheritdoc
     */
    public subscribe(listener: () => void) {
        this.requiresBackingStore("subscribe");
        return this.backingStore!.subscribe(listener);
    }

    /**
     * @inheritdoc
     */
    public replaceReducer(nextReducer: Reducer<GState, any>): void {
        this.requiresBackingStore("replaceReducer");
        return this.backingStore!.replaceReducer(nextReducer);
    }

    ///
    /// Utilities
    ///

    /**
     * Assert that a method requires a backing store and throw an error otherwise.
     */
    private requiresBackingStore(methodName: string) {
        if (!this.backingStore) {
            throw new Error(`MockStore.${methodName}() is not available without a backing store.`);
        }
    }
}
