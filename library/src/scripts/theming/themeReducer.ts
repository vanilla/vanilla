/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemeActions from "@library/theming/ThemeActions";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import produce from "immer";

export interface ITheme {
    assets: IThemeAssets;
}

export interface IThemeAssets {
    fonts?: { data: IThemeFont[] };
    logo?: IThemeExternalAsset;
    mobileLogo?: IThemeExternalAsset;
    variables?: IThemeVariables;
}

export interface IThemeFont {
    name: string;
    url: string;
    fallbacks: string[];
}

export interface IThemeExternalAsset {
    type: string;
    url: string;
}

export interface IThemeVariables {
    [key: string]: string;
}

export interface IThemeState {
    assets: ILoadable<IThemeAssets>;
}

export const INITIAL_STATE: IThemeState = {
    assets: {
        status: LoadStatus.PENDING,
    },
};

export const themeReducer = produce(
    reducerWithInitialState(INITIAL_STATE)
        .case(ThemeActions.getAssets.started, state => {
            state.assets.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.getAssets.done, (state, payload) => {
            state.assets.status = LoadStatus.SUCCESS;
            state.assets.data = payload.result.assets;
            return state;
        })
        .case(ThemeActions.getAssets.failed, (state, payload) => {
            if (payload.error.response && payload.error.response.status === 404) {
                // This theme just doesn't have variables. Use the defaults.
                state.assets.data = {};
                state.assets.status = LoadStatus.SUCCESS;
                return state;
            } else {
                state.assets.status = LoadStatus.ERROR;
                state.assets.error = payload.error;
                return state;
            }
        }),
);
