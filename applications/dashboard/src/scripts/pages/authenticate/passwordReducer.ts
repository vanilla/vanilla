/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as actions from "@dashboard/pages/authenticate/passwordActions";
import { IPasswordState } from "@dashboard/@types/state";
import { LoadStatus } from "@library/@types/api/core";

const initialState: IPasswordState = {
    status: LoadStatus.PENDING,
};

export default function passwordReducer(
    state: IPasswordState = initialState,
    action: actions.ActionTypes,
): IPasswordState {
    switch (action.type) {
        case actions.POST_AUTHENTICATE_PASSWORD_REQUEST:
            return {
                ...state,
                status: LoadStatus.LOADING,
            };
        case actions.POST_AUTHENTICATE_PASSWORD_SUCCESS:
            return {
                status: LoadStatus.SUCCESS,
                data: action.payload.data,
                error: undefined,
            };
        case actions.POST_AUTHENTICATE_PASSWORD_ERROR:
            return {
                ...state,
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        default:
            return state;
    }
}
