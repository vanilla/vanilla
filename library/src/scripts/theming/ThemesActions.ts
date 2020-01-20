/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction, useReduxActions } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";
import { ITheme } from "@library/theming/themeReducer";

const actionCreator = actionCreatorFactory("@@themes");

export enum ThemeType {
    DB = "themeDB",
    FS = "themeFile",
}

export enum PreviewStatusType {
    PREVIEW = "preview",
    APPLY = "apply",
    CANCEL = "cancel",
}
export interface IManageTheme extends ITheme {
    name: string;
    type: ThemeType;
    current: boolean;
    themeID: string;
    parentTheme: string;
    parentVersion: string;
    preview: {
        [key: string]: string;
    };
}

type IGetAllThemeResponse = IManageTheme[];

interface IPutCurrentThemeRequest {
    themeID: number | string;
    type: PreviewStatusType;
}
/**
 * Actions for working with resources from the /api/v2/knowledge-bases endpoint.
 */

export default class ThemesActions extends ReduxActions {
    public static readonly getAllThemes_ACS = actionCreator.async<{}, IGetAllThemeResponse, IApiError>(
        "GET_ALL_THEMES",
    );

    public static readonly putCurrentThemeACs = actionCreator.async<
        { themeID: number | string },
        IManageTheme,
        IApiError
    >("PUT_CURRENT");

    public static readonly putPreviewThemeACs = actionCreator.async<IPutCurrentThemeRequest, IManageTheme, IApiError>(
        "PUT_PREVIEW",
    );

    public getAllThemes = () => {
        const thunk = bindThunkAction(ThemesActions.getAllThemes_ACS, async () => {
            const params = { expand: "all" };
            const response = await this.api.get(`/themes/`, { params });

            return response.data;
        })();
        return this.dispatch(thunk);
    };

    public putCurrentTheme = (themeID: number | string) => {
        const body = { themeID };
        const thunk = bindThunkAction(ThemesActions.putCurrentThemeACs, async () => {
            const response = await this.api.put(`/themes/current`, body);

            return response.data;
        })(body);
        return this.dispatch(thunk);
    };

    public putPreviewTheme = (options: IPutCurrentThemeRequest) => {
        const { themeID, type } = options;
        const thunk = bindThunkAction(ThemesActions.putPreviewThemeACs, async () => {
            const response = await this.api.put(`/themes/preview`, { themeID });

            return response.data;
        })(options);
        return this.dispatch(thunk);
    };
}

export function useThemesActions() {
    return useReduxActions(ThemesActions);
}
