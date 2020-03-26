/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemeActions from "@library/theming/ThemeActions";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import produce from "immer";

export enum ThemeType {
    DB = "themeDB",
    FS = "themeFile",
}

export interface ITheme {
    assets: IThemeAssets;
    features: Record<string, boolean>;
    supportedSections?: string[];
    preview: Record<string, any> & { info: Record<string, any> };
    themeID: string | number;
    name: string;
    type: ThemeType;
    parentTheme?: string;
    current: boolean;
    version: string;
}

export interface IThemeAssets {
    fonts?: { data: IThemeFont[] };
    logo?: IThemeExternalAsset;
    mobileLogo?: IThemeExternalAsset;
    variables?: IThemeVariables;
    header?: IThemeHeader;
    footer?: IThemeFooter;
    javascript?: string;
    styles?: string;
}
export interface IThemeHeader {
    data?: string;
    type: string;
}
export interface IThemeFooter {
    data?: string;
    type: string;
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

export type IThemeVariables = Record<string, any>;

export interface IThemeState {
    assets: ILoadable<IThemeAssets>;
    forcedVariables: IThemeVariables | null;
}

export const INITIAL_THEME_STATE: IThemeState = {
    assets: {
        status: LoadStatus.PENDING,
    },
    forcedVariables: null,
};

export const themeReducer = produce(
    reducerWithInitialState(INITIAL_THEME_STATE)
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
        })
        .case(ThemeActions.forceVariablesAC, (state, payload) => {
            state.forcedVariables = payload;
            return state;
        }),
);
