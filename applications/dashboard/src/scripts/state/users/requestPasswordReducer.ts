/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

import * as actions from "@dashboard/state/users/requestPasswordActions";
import { IRequestPasswordState } from "@dashboard/@types/state";
import { LoadStatus } from "@dashboard/@types/api";

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
        default:
            return state;
    }
}
