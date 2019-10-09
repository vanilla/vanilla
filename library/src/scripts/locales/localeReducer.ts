/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { produce } from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILocale } from "@vanilla/i18n";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { getAllLocalesACs } from "@library/locales/localeActions";

export interface ILocaleState {
    locales: ILoadable<ILocale[]>;
}

const DEFAULT_LOCALE_STATE = {
    locales: {
        status: LoadStatus.PENDING,
    },
};

export const localeReducer = produce(
    reducerWithInitialState<ILocaleState>(DEFAULT_LOCALE_STATE)
        .case(getAllLocalesACs.started, (nextState, payload) => {
            nextState.locales.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(getAllLocalesACs.done, (nextState, payload) => {
            nextState.locales.status = LoadStatus.SUCCESS;
            nextState.locales.data = payload.result;
            return nextState;
        })
        .case(getAllLocalesACs.failed, (nextState, payload) => {
            nextState.locales.status = LoadStatus.ERROR;
            nextState.locales.error = payload.error;
            return nextState;
        }),
);
