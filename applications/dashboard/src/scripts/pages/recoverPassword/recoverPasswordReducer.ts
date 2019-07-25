/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import * as actions from "@dashboard/pages/recoverPassword/recoverPasswordActions";
import { IRequestPasswordState } from "@dashboard/@types/state";
import { LoadStatus } from "@library/@types/api/core";

const initialState: IRequestPasswordState = {
    status: LoadStatus.PENDING,
};

export default function passwordReducer(
    state: IRequestPasswordState = initialState,
    action: actions.ActionTypes,
): IRequestPasswordState {
    switch (action.type) {
        case actions.POST_REQUEST_PASSWORD_REQUEST:
            return {
                ...state,
                status: LoadStatus.LOADING,
            };
        case actions.POST_REQUEST_PASSWORD_SUCCESS:
            return {
                status: LoadStatus.SUCCESS,
                data: action.payload.data,
                error: undefined,
            };
        case actions.POST_REQUEST_PASSWORD_ERROR:
            return {
                ...state,
                status: LoadStatus.ERROR,
                error: action.payload,
            };
        case actions.AFTER_REQUEST_PASSWORD_SUCCESS_NAVIGATE:
            return initialState;
        default:
            return state;
    }
}
