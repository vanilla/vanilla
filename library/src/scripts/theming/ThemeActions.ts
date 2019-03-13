/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import ReduxActions, { bindThunkAction } from "@library/state/ReduxActions";
import actionCreatorFactory from "typescript-fsa";
import { IThemeVariables } from "@library/theming/themeReducer";
import { IApiError } from "@library/@types/api";

const createAction = actionCreatorFactory("@@themes");

export default class ThemeActions extends ReduxActions {
    public static getVariables = createAction.async<{ themeKey: string }, IThemeVariables, IApiError>("GET_VARIABLES");
    public getVariables = (themeKey: string) => {
        const { theme } = this.getState();
        if (theme.variables.data) {
            return theme.variables.data;
        }

        const apiThunk = bindThunkAction(ThemeActions.getVariables, async () => {
            const response = await this.api.get(`/themes/${themeKey}/assets/variables.json`);
            return response.data;
        })({ themeKey });
        return this.dispatch(apiThunk);
    };
}
