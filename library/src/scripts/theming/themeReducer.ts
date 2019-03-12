/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemeActions from "@library/theming/ThemeActions";
import { ILoadable, LoadStatus } from "@library/@types/api";
import produce from "immer";

export interface IThemeVariables {
    [key: string]: string;
}

export interface IThemeState {
    variables: ILoadable<IThemeVariables>;
}

export const INITIAL_STATE: IThemeState = {
    variables: {
        status: LoadStatus.PENDING,
    },
};

export const themeReducer = produce(
    reducerWithInitialState(INITIAL_STATE)
        .case(ThemeActions.getVariables.started, state => {
            state.variables.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.getVariables.done, (state, payload) => {
            state.variables.status = LoadStatus.SUCCESS;
            state.variables.data = payload.result;
            return state;
        })
        .case(ThemeActions.getVariables.failed, (state, payload) => {
            if (payload.error.response.status === 404) {
                // This theme just doesn't have variables. Use the defaults.
                state.variables.data = {};
                state.variables.status = LoadStatus.SUCCESS;
                return state;
            } else {
                state.variables.status = LoadStatus.ERROR;
                state.variables.error = payload.error;
                return state;
            }
        }),
);
