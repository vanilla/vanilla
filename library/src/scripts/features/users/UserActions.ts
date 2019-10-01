/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { IApiError, LoadStatus } from "@library/@types/api/core";
import { IMe, IMeCounts } from "@library/@types/api/users";
import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";

const createAction = actionCreatorFactory("@@users");

// The duration we wait to check for new counts.
const COUNT_CACHE_PERIOD = 60; // 60 Seconds

/**
 * Redux actions for the users data.
 */
export default class UserActions extends ReduxActions {
    public static getMeACs = createAction.async<{}, IMe, IApiError>("GET_ME");
    /**
     * Request the currently signed in user data if it's not loaded.
     */
    public getMe = () => {
        const currentUser = this.getState().users.current;
        if (currentUser.status === LoadStatus.LOADING) {
            // Don't request the user more than once.
            return;
        }
        const apiThunk = bindThunkAction(UserActions.getMeACs, async () => {
            const response = await this.api.get("/users/me");
            return response.data;
        })();

        return this.dispatch(apiThunk);
    };

    public static getCountsACs = createAction.async<{}, { counts: IMeCounts }, IApiError>("GET_ME_COUNTS");

    /**
     * Check if we have valid count data and request it again if it is stale.
     */
    public checkCountData = () => {
        const currentTime = new Date().getTime();
        const { lastRequested } = this.getState().users.countInformation;

        if (lastRequested !== null && currentTime < lastRequested + COUNT_CACHE_PERIOD) {
            // Bailout if we've requested this data within the previous cache period
            return;
        }
        const apiThunk = bindThunkAction(UserActions.getCountsACs, async () => {
            const response = await this.api.get("/users/me-counts");
            return response.data;
        })();

        return this.dispatch(apiThunk);
    };
}
