/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

import ReduxActions, { bindThunkAction } from "@library/redux/ReduxActions";
import { actionCreatorFactory } from "typescript-fsa";
import { IApiError } from "@library/@types/api/core";
import { ITranslationProperty } from "@vanilla/i18n";
import { ITranslationsGlobalStoreState } from "./translationReducer";
import { logWarning, RecordID } from "@vanilla/utils";
import { validateTranslationProperties } from "./validateTranslationProperties";

const createAction = actionCreatorFactory("@@translations");

export interface ITranslationApiItem {
    locale: string;
    propertyName: string;
    propertyType: string;
    translation: string;
    recordType: string;
    recordID?: RecordID;
    recordKey?: string;
    translationPropertyKey: string;
}

interface IGetTranslationsRequest {
    recordType?: string;
    recordID?: number;
    recordIDs?: RecordID[];
    recordKey?: string;
    recordKeys?: string[];
    locale: string;
}

type IGetTranslationsResponse = ITranslationApiItem[];

type IPatchTranslationsRequest = ITranslationApiItem[];
interface IPatchTranslationsResponse {}

type IPatchRemoveItem = Omit<ITranslationApiItem, "translation">;
type IPatchRemoveTranslationsRequest = IPatchRemoveItem[];
interface IPatchRemoveTranslationsResponse {}

export function makeTranslationKey(
    property: Pick<ITranslationApiItem, "propertyName" | "recordType" | "recordID" | "recordKey">,
) {
    return `${property.recordType}.${property.recordID || property.recordKey}.${property.propertyName}`;
}

export class TranslationActions extends ReduxActions<ITranslationsGlobalStoreState> {
    ///
    /// Constants
    ///

    public static updateForm = createAction<{ field: string; translation: string }>("UPDATE_FORM");

    public static clearForm = createAction("CLEAR_FORM");

    public static init = createAction<{
        resource: string;
        translationLocale: string;
    }>("INIT");

    public static getTranslationsACs = createAction.async<IGetTranslationsRequest, IGetTranslationsResponse, IApiError>(
        "GET",
    );

    public static patchTranslationsACs = createAction.async<
        IPatchTranslationsRequest,
        IPatchTranslationsResponse,
        IApiError
    >("PATCH");

    public static patchRemoveTranslationsACs = createAction.async<
        IPatchRemoveTranslationsRequest,
        IPatchRemoveTranslationsResponse,
        IApiError
    >("PATCH_REMOVE");

    ///
    /// Simple actions
    ///

    public updateForm = (field: string, translation: string) => {
        this.dispatch(TranslationActions.updateForm({ field, translation }));
    };

    public clearForm = this.bindDispatch(TranslationActions.clearForm);

    public init = this.bindDispatch(TranslationActions.init);

    ///
    /// Thunks
    ///

    /**
     * Get all translations from the API based on the given properties.
     */
    public getTranslationsForProperties = async (properties: ITranslationProperty[]) => {
        const { translationLocale } = this.getState().translations;
        const firstProperty = properties[0];
        if (!firstProperty) {
            logWarning("Attempted to fetch translations, but no properties were provided");
            return;
        }

        if (!translationLocale) {
            logWarning("Attempted to fetch translations, but no locale was configured.");
            return;
        }

        validateTranslationProperties(properties);

        const recordIDs = new Set(properties.map((prop) => prop.recordID!).filter((id) => id != null));
        const recordKeys = new Set(properties.map((prop) => prop.recordKey!).filter((key) => key != null));

        const query: IGetTranslationsRequest = {
            locale: translationLocale,
            recordType: firstProperty.recordType,
        };

        if (recordIDs.size > 0) {
            query.recordIDs = Array.from(recordIDs);
        } else if (recordKeys.size > 0) {
            query.recordKeys = Array.from(recordKeys);
        }

        return await this.getTranslations(query);
    };

    /**
     * Publish all form values to the API based on the given properties.
     */
    public publishForm = async (properties: ITranslationProperty[]) => {
        const { translationLocale, translationsByLocale } = this.getState().translations;
        const { formTranslations } = this.getState().translations;

        const existingTranslations = translationsByLocale[translationLocale];

        const publishFieldValues: ITranslationApiItem[] = [];
        const deleteFieldValues: IPatchRemoveItem[] = [];

        for (const [key, translation] of Object.entries(formTranslations)) {
            const property = properties.find((prop) => {
                const propKey = makeTranslationKey(prop);
                return key === propKey;
            });

            const existingTranslation = existingTranslations.data?.[key] ?? null;

            if (property) {
                if (existingTranslation && translation === "") {
                    deleteFieldValues.push({
                        ...property,
                        translationPropertyKey: makeTranslationKey(property),
                        locale: translationLocale,
                    });
                } else if (translation !== "") {
                    publishFieldValues.push({
                        translationPropertyKey: makeTranslationKey(property),
                        ...property,
                        locale: translationLocale!,
                        translation,
                    });
                }
            }
        }

        const deletePromise =
            deleteFieldValues.length > 0 ? this.patchRemoveTranslations(deleteFieldValues) : Promise.resolve();
        const patchPromise =
            publishFieldValues.length > 0 ? this.patchTranslations(publishFieldValues) : Promise.resolve();
        await Promise.all([deletePromise, patchPromise]);
    };

    /**
     * Get translations from the /api/v2/translations endpoint.
     */
    public getTranslations = (params: IGetTranslationsRequest) => {
        const { resource } = this.getState().translations;
        const thunk = bindThunkAction(TranslationActions.getTranslationsACs, async () => {
            const response = await this.api.get(`/translations/${resource}`, { params });
            return response.data;
        })(params);

        return this.dispatch(thunk);
    };

    /**
     * Submit translations from the /api/v2/translations endpoint.
     */
    public patchTranslations = (translations: IPatchTranslationsRequest) => {
        const { resource } = this.getState().translations;
        const thunk = bindThunkAction(TranslationActions.patchTranslationsACs, async () => {
            const response = await this.api.patch(`/translations/${resource}`, translations);
            return response.data;
        })(translations);

        return this.dispatch(thunk);
    };

    /**
     * Delete translations using the /api/v2/translations/:resource/remove endpoint.
     */
    public patchRemoveTranslations = (translations: IPatchRemoveTranslationsRequest) => {
        const { resource } = this.getState().translations;
        const thunk = bindThunkAction(TranslationActions.patchRemoveTranslationsACs, async () => {
            const response = await this.api.patch(`/translations/${resource}/remove`, translations);
            return response.data;
        })(translations);

        return this.dispatch(thunk);
    };
}
