/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { ILocale } from "@vanilla/i18n";
import { actionCreatorFactory } from "typescript-fsa";
import { IApiError, LoadStatus } from "@library/@types/api/core";
import { bindThunkAction } from "@library/redux/ReduxActions";
import apiv2 from "@library/apiv2";
import getStore from "@library/redux/getStore";

const createAction = actionCreatorFactory("@@locales");
export const getAllLocalesACs = createAction.async<{}, ILocale[], IApiError>("GET_ALL");

/**
 * Thunk for fetching locales.
 */
export function fetchLocalesFromApi(force?: boolean): Promise<ILocale[]> {
    const { dispatch, getState } = getStore();

    const localeLoadable = getState().locales.locales;
    if (!force && localeLoadable.status === LoadStatus.SUCCESS) {
        return Promise.resolve(localeLoadable.data!);
    }

    const apiThunk = bindThunkAction(getAllLocalesACs, async () => {
        const response = await apiv2.get(`/locales`);
        return response.data;
    })();
    return apiThunk(dispatch, getState, {});
}
