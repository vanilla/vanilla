/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemeActions from "@library/theming/ThemeActions";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import produce from "immer";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { IUserFragment } from "@library/@types/api/users";
import { RecordID } from "@vanilla/utils";

export enum ThemeType {
    DB = "themeDB",
    FS = "themeFile",
}

export interface ITheme {
    assets: IThemeAssets;
    features: Record<string, boolean>;
    supportedSections?: string[];
    preview: IThemePreview;
    themeID: RecordID;
    name: string;
    type: ThemeType;
    parentTheme?: string;
    current: boolean;
    version: string;
    insertUser?: IUserFragment;
    dateInserted?: string;
    revisionID: number | null;
}

export interface IThemePreview {
    info?: Record<
        string,
        {
            type: string;
            value: string;
        }
    >;
    imageUrl?: string | null;
    variables?: {
        globalBg?: string | null;
        globalFg?: string | null;
        globalPrimary?: string | null;
        titleBarBg?: string | null;
        titleBarFg?: string | null;
        backgroundImage?: string | null;
    };
}

export interface IThemeRevision extends ITheme {
    active: boolean;
    revisionID: number;
}

interface IThemeAsset<DataType = string> {
    type: string;
    url?: string;
    data?: DataType;
}

export interface IThemeAssets {
    fonts?: IThemeAsset<IThemeFont[]>;
    logo?: IThemeAsset;
    mobileLogo?: IThemeAsset;
    variables?: IThemeAsset<IThemeVariables>;
    header?: IThemeAsset;
    footer?: IThemeAsset;
    javascript?: IThemeAsset;
    styles?: IThemeAsset;
}

export interface IThemeFont {
    name: string;
    url: string;
    fallbacks?: string[];
}

export type IThemeVariables = Record<string, any>;

export interface IThemeState {
    assets: ILoadable<IThemeAssets>;
    forcedVariables: IThemeVariables | null;
    themeRevisions: ILoadable<IThemeRevision[]>;
    upDateRevision: ILoadable<ITheme>;
}

export interface IThemesStoreState extends ICoreStoreState {
    theme: IThemeState;
}

export const INITIAL_THEME_STATE: IThemeState = {
    assets: {
        status: LoadStatus.PENDING,
    },
    forcedVariables: null,
    themeRevisions: {
        status: LoadStatus.PENDING,
    },
    upDateRevision: {
        status: LoadStatus.PENDING,
    },
};

export const themeReducer = produce(
    reducerWithInitialState(INITIAL_THEME_STATE)
        .case(ThemeActions.getAssets.started, (state) => {
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
        })
        .case(ThemeActions.getThemeRevisions_ACs.started, (state, payload) => {
            state.themeRevisions.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.getThemeRevisions_ACs.failed, (state, payload) => {
            if (state.themeRevisions) {
                state.themeRevisions.status = LoadStatus.ERROR;
                state.themeRevisions.error = payload.error;
            }
            return state;
        })
        .case(ThemeActions.getThemeRevisions_ACs.done, (state, payload) => {
            if (state.themeRevisions) {
                state.themeRevisions.status = LoadStatus.SUCCESS;
                state.themeRevisions.data = payload.result;
            }
            return state;
        })
        .case(ThemeActions.patchTheme_ACs.started, (state, payload) => {
            state.upDateRevision.status = LoadStatus.LOADING;
            return state;
        })
        .case(ThemeActions.patchTheme_ACs.failed, (state, payload) => {
            state.upDateRevision.status = LoadStatus.ERROR;
            state.upDateRevision.error = payload.error;
            return state;
        })
        .case(ThemeActions.patchTheme_ACs.done, (state, payload) => {
            state.upDateRevision.status = LoadStatus.SUCCESS;
            state.upDateRevision.data = payload.result;
            const { revisionID, themeID } = payload.result;

            // Go through any active revisions if we have them, and modify their active status.
            if (state.themeRevisions.data) {
                state.themeRevisions.data = state.themeRevisions.data.map((revision) => {
                    if (revision.themeID !== themeID) {
                        return revision;
                    }

                    revision.active = revision.revisionID === revisionID;
                    return revision;
                });
            }
            return state;
        }),
);

export function useGetThemeState() {
    return useSelector((state: IThemesStoreState) => {
        return state.theme;
    });
}
