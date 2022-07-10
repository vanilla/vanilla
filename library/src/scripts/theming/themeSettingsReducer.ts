/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import ThemeActions from "@library/theming/ThemeActions";
import { useSelector } from "react-redux";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import { ThemeType, ITheme } from "@library/theming/themeReducer";
import { RecordID } from "@vanilla/utils";

export interface IThemesState {
    themes: ILoadable<{
        currentTheme: ITheme;
        templates: ITheme[];
        themes: ITheme[];
    }>;
    applyStatus: ILoadable<{ themeID: RecordID }>;
    previewStatus: ILoadable<{ themeID: RecordID }>;
    deleteThemeByID: {
        [themeID: number]: ILoadable<{}>;
    };
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
    deleteThemeByID: {},
};

export const themeSettingsReducer = produce(
    reducerWithInitialState<IThemesState>(DEFAULT_THEMES_STATE)
        .case(ThemeActions.getAllThemes_ACS.started, (nextState, payload) => {
            nextState.themes.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(ThemeActions.getAllThemes_ACS.done, (nextState, payload) => {
            let currentTheme;
            let templates: ITheme[] = [];
            let themes: ITheme[] = [];

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
        .case(ThemeActions.getAllThemes_ACS.failed, (nextState, payload) => {
            nextState.themes.status = LoadStatus.ERROR;
            nextState.themes.error = payload.error;
            return nextState;
        })
        .case(ThemeActions.putCurrentThemeACs.started, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.LOADING;
            nextState.applyStatus.data = {
                themeID: payload.themeID,
            };
            return nextState;
        })
        .case(ThemeActions.putCurrentThemeACs.done, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.SUCCESS;
            nextState.applyStatus.error = undefined;

            if (nextState.themes.data) {
                nextState.themes.data.currentTheme = payload.result;

                nextState.themes.data.themes = nextState.themes.data.themes.map((theme) => {
                    return {
                        ...theme,
                        current: theme.themeID === payload.result.themeID,
                    };
                });

                nextState.themes.data.templates = nextState.themes.data.templates.map((templates) => {
                    return {
                        ...templates,
                        current: templates.themeID === payload.result.themeID,
                    };
                });
            }

            return nextState;
        })
        .case(ThemeActions.putCurrentThemeACs.failed, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.ERROR;
            nextState.applyStatus.error = payload.error;
            return nextState;
        })
        .case(ThemeActions.putPreviewThemeACs.started, (nextState, payload) => {
            nextState.previewStatus.status = LoadStatus.LOADING;
            nextState.previewStatus.data = {
                themeID: payload.themeID,
            };
            return nextState;
        })
        .case(ThemeActions.putPreviewThemeACs.done, (nextState, payload) => {
            nextState.previewStatus.status = LoadStatus.SUCCESS;
            nextState.previewStatus.error = undefined;

            return nextState;
        })
        .case(ThemeActions.putPreviewThemeACs.failed, (nextState, payload) => {
            nextState.applyStatus.status = LoadStatus.ERROR;
            nextState.applyStatus.error = payload.error;
            return nextState;
        })
        .case(ThemeActions.deleteThemeACs.started, (nextState, payload) => {
            nextState.deleteThemeByID[payload.themeID] = {
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(ThemeActions.deleteThemeACs.done, (nextState, payload) => {
            nextState.deleteThemeByID[payload.params.themeID] = {
                status: LoadStatus.SUCCESS,
            };
            if (nextState.themes.data) {
                nextState.themes.data.themes = nextState.themes.data?.themes.filter((existingTemplate) => {
                    if (existingTemplate.themeID === payload.params.themeID) {
                        return false;
                    } else {
                        return true;
                    }
                });
            }
            return nextState;
        })
        .case(ThemeActions.deleteThemeACs.failed, (nextState, payload) => {
            nextState.deleteThemeByID[payload.params.themeID] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return nextState;
        }),
);

export function useThemeSettingsState() {
    return useSelector((state: IThemesStoreState) => {
        return state.themeSettings;
    });
}
