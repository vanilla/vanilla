/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemesActions, { IManageTheme, ThemeType } from "@library/theming/ThemesActions";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

export interface IThemesState {
    themes: ILoadable<{
        currentTheme: IManageTheme;
        templates: IManageTheme[];
        themes: IManageTheme[];
    }>;
    applyStatus: ILoadable<{ themeID: number | string }>;
    previewStatus: ILoadable<{ themeID: number | string }>;
}

export interface IThemesStoreState extends ICoreStoreState {
    themeSettings: IThemesState;
}

const DEFAULT_THEMES_STATE: IThemesState = {
    themes: {
        status: LoadStatus.PENDING,
    },
    applyStatus: {
        status: LoadStatus.PENDING,
    },
    previewStatus: {
        status: LoadStatus.PENDING,
    },
};

export const themeSettingsReducer = produce(
    reducerWithInitialState<IThemesState>(DEFAULT_THEMES_STATE)
        .case(ThemesActions.getAllThemes_ACS.started, (nextState, payload) => {
            nextState.themes.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(ThemesActions.getAllThemes_ACS.done, (nextState, payload) => {
            let currentTheme;
            let templates: IManageTheme[] = [];
            let themes: IManageTheme[] = [];

            for (const theme of payload.result) {
                if (theme.current) {
                    currentTheme = theme;
                }
                if (theme.type === ThemeType.FS) {
                    templates.push(theme);
                } else {
                    themes.push(theme);
                }
            }

            nextState.themes.status = LoadStatus.SUCCESS;
            nextState.themes.data = {
                currentTheme,
                templates,
                themes,
            };
            return nextState;
        })
        .case(ThemesActions.getAllThemes_ACS.failed, (nextState, payload) => {
            nextState.themes.status = LoadStatus.ERROR;
            nextState.themes.error = payload.error;
            return nextState;
        })
        .case(ThemesActions.putCurrentThemeACs.started, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.LOADING;
            nextState.applyStatus.data = {
                themeID: payload.themeID,
            };
            return nextState;
        })
        .case(ThemesActions.putCurrentThemeACs.done, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.SUCCESS;
            nextState.applyStatus.error = undefined;

            if (nextState.themes.data) {
                nextState.themes.data.currentTheme = payload.result;
            }

            return nextState;
        })
        .case(ThemesActions.putCurrentThemeACs.failed, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.ERROR;
            nextState.applyStatus.error = payload.error;
            return nextState;
        })
        .case(ThemesActions.putPreviewThemeACs.started, (nextState, payload) => {
            nextState.previewStatus.status = LoadStatus.LOADING;
            nextState.previewStatus.data = {
                themeID: payload.themeID,
            };
            return nextState;
        })
        .case(ThemesActions.putPreviewThemeACs.done, (nextState, payload) => {
            nextState.previewStatus.status = LoadStatus.SUCCESS;
            nextState.previewStatus.error = undefined;

            return nextState;
        })
        .case(ThemesActions.putPreviewThemeACs.failed, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.ERROR;
            nextState.applyStatus.error = payload.error;
            return nextState;
        }),
);

export function useThemeSettingsState() {
    return useSelector((state: IThemesStoreState) => {
        return state.themeSettings;
    });
}
