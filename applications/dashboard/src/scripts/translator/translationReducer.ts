/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import { reducerWithInitialState } from "typescript-fsa-reducers";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { ITranslationApiItem, TranslationActions } from "./TranslationActions";
import { ICoreStoreState } from "@library/redux/reducerRegistry";
import produce from "immer";

export interface ITranslationState {
    translationsByLocale: {
        [localeKey: string]: ILoadable<{
            [translationPropertyKey: string]: ITranslationApiItem;
        }>;
    };
    submitLoadable: ILoadable<{}>;
    formTranslations: {
        [key: string]: string;
    };
    resource: string | null;
    translationLocale: string;
}

export interface ITranslationsGlobalStoreState extends ICoreStoreState {
    translations: ITranslationState;
}

export const INITIAL_TRANSLATION_STATE: ITranslationState = {
    translationsByLocale: {},
    submitLoadable: {
        status: LoadStatus.PENDING,
    },
    formTranslations: {},
    resource: null,
    translationLocale: "en",
};

export const translationReducer = produce(
    reducerWithInitialState<ITranslationState>(INITIAL_TRANSLATION_STATE)
        .case(TranslationActions.updateForm, (nextState, payload) => {
            nextState.formTranslations[payload.field] = payload.translation;
            return nextState;
        })
        .case(TranslationActions.clearForm, (nextState) => {
            nextState.formTranslations = {};
            return nextState;
        })
        .case(TranslationActions.init, (nextState, payload) => {
            return {
                ...nextState,
                ...payload,
                formTranslations: {},
                submitLoadable: INITIAL_TRANSLATION_STATE.submitLoadable,
            };
        })
        .case(TranslationActions.getTranslationsACs.started, (nextState, payload) => {
            const { locale } = payload;
            const existingTranslations = nextState.translationsByLocale[locale];
            nextState.translationsByLocale[locale] = {
                ...existingTranslations,
                status: LoadStatus.LOADING,
            };
            return nextState;
        })
        .case(TranslationActions.getTranslationsACs.done, (nextState, payload) => {
            const newData = {};
            for (const item of payload.result) {
                newData[item.translationPropertyKey] = item;
            }
            nextState.translationsByLocale[payload.params.locale] = {
                status: LoadStatus.SUCCESS,
                data: newData,
            };
            return nextState;
        })
        .case(TranslationActions.getTranslationsACs.failed, (nextState, payload) => {
            nextState.translationsByLocale[payload.params.locale] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return nextState;
        })
        .case(TranslationActions.patchTranslationsACs.started, (nextState, payload) => {
            nextState.submitLoadable.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(TranslationActions.patchTranslationsACs.done, (nextState, payload) => {
            nextState.submitLoadable.status = LoadStatus.SUCCESS;

            // Clear any errors.
            delete nextState.submitLoadable.error;

            // Clear the form.
            nextState.formTranslations = {};

            // Update our local data.
            const updateLocale = payload.params[0]?.locale;
            if (updateLocale) {
                // Some items were updated. Update them locally.
                const existingTranslations = nextState.translationsByLocale[updateLocale];
                const existingData = existingTranslations.data || {};
                const newData = {};
                for (const item of payload.params) {
                    newData[item.translationPropertyKey] = item;
                }
                nextState.translationsByLocale[updateLocale] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...existingData,
                        ...newData,
                    },
                };
            }

            // Update patched
            return nextState;
        })
        .case(TranslationActions.patchTranslationsACs.failed, (nextState, payload) => {
            nextState.submitLoadable.status = LoadStatus.ERROR;
            nextState.submitLoadable.error = payload.error;
            return nextState;
        })
        .case(TranslationActions.patchRemoveTranslationsACs.started, (nextState, payload) => {
            nextState.submitLoadable.status = LoadStatus.LOADING;
            return nextState;
        })
        .case(TranslationActions.patchRemoveTranslationsACs.done, (nextState, payload) => {
            nextState.submitLoadable.status = LoadStatus.SUCCESS;

            // Clear any errors.
            delete nextState.submitLoadable.error;

            // Clear the form.
            nextState.formTranslations = {};

            // Update our local data.
            const updateLocale = payload.params[0]?.locale;
            if (updateLocale) {
                // Some items were updated. Update them locally.
                const existingTranslations = nextState.translationsByLocale[updateLocale];
                for (const item of payload.params) {
                    delete existingTranslations.data![item.translationPropertyKey];
                }
            }

            // Update patched
            return nextState;
        })
        .case(TranslationActions.patchRemoveTranslationsACs.failed, (nextState, payload) => {
            nextState.submitLoadable.status = LoadStatus.ERROR;
            nextState.submitLoadable.error = payload.error;
            return nextState;
        }),
);
