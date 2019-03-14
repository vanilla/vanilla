/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { ITheme } from "@library/theming/themeReducer";
import { IApiError } from "@library/@types/api/core";

const createAction = actionCreatorFactory("@@themes");

export default class ThemeActions extends ReduxActions {
    public static getAssets = createAction.async<{ themeKey: string }, ITheme, IApiError>("GET");
    public getAssets = (themeKey: string) => {
        const { theme } = this.getState();
        if (theme.assets.data) {
            return theme.assets.data;
        }

        const apiThunk = bindThunkAction(ThemeActions.getAssets, async () => {
            const response = await this.api.get(`/themes/${themeKey}`);
            return response.data;
        })({ themeKey });
        return this.dispatch(apiThunk);
    };
}
