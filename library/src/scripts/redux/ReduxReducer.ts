/**
 * @author Adam (charrondev) Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IAction } from "@library/redux/ReduxActions";

/**
 * Base class for creating a redux reducer.
 */
export default abstract class ReduxReducer<S> {
    /**
     * The reducer function for redux.
     */
    public abstract reducer: (state: S, action: IAction<any>) => S;
}
