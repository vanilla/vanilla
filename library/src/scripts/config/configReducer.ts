/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IAddon, ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import { IApiError, IFieldError, IServerError, Loadable, LoadStatus } from "@library/@types/api/core";
import {
    getConfigsByKeyThunk,
    patchConfigThunk,
    getAllTranslationServicesThunk,
    putTranslationServiceThunk,
    getAddonsByTypeThunk,
    patchAddonByIdThunk,
    getAvailableLocalesThunk,
    getServicesByLocaleThunk,
    updateConfigsLocal,
} from "@library/config/configActions";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { indexArrayByKey, notEmpty, stableObjectHash } from "@vanilla/utils";
import { useDispatch } from "react-redux";

type ConfigValuesByKey = Record<string, any>;

type ServiceValuesByKey = Record<string, any>;
export interface IConfigState {
    configsByLookupKey: Record<number, Loadable<ConfigValuesByKey>>;
    configPatchesByID: Record<string, Loadable<{}>>;
    machineTranslation: {
        services: {
            status: LoadStatus;
            error?: any;
            data?: ITranslationService[];
        };
        put: {
            status: LoadStatus;
            error?: any;
            data?: any;
        };
    };
    addons: Record<string, Loadable<IAddon[]>>;
    addonPatchByID: Record<string, Loadable<{}>>;
    locales: {
        status: LoadStatus;
        error?: any;
        data?: any;
    };
    localeTranslationService: Record<string, Loadable<ServiceValuesByKey>>;
}

export const INITIAL_CONFIG_STATE: IConfigState = {
    configsByLookupKey: {},
    configPatchesByID: {},
    machineTranslation: {
        services: {
            status: LoadStatus.PENDING,
        },
        put: {
            status: LoadStatus.PENDING,
        },
    },
    addons: {},
    addonPatchByID: {},
    locales: {
        status: LoadStatus.PENDING,
    },
    localeTranslationService: {},
};

export const configSlice = createSlice({
    name: "config",
    initialState: INITIAL_CONFIG_STATE,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(getConfigsByKeyThunk.pending, (state, action) => {
                state.configsByLookupKey[stableObjectHash(action.meta.arg)] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(getConfigsByKeyThunk.fulfilled, (state, action) => {
                state.configsByLookupKey[stableObjectHash(action.meta.arg)] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(getConfigsByKeyThunk.rejected, (state, action) => {
                state.configsByLookupKey[stableObjectHash(action.meta.arg)] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(patchConfigThunk.pending, (state, action) => {
                state.configPatchesByID[action.meta.arg.watchID] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(patchConfigThunk.fulfilled, (state, action) => {
                state.configPatchesByID[action.meta.arg.watchID] = {
                    status: LoadStatus.SUCCESS,
                    data: {},
                };

                // Update existing config values.
                for (const configKeys of Object.values(state.configsByLookupKey)) {
                    for (const [key, value] of Object.entries(action.meta.arg.values)) {
                        if (configKeys?.data) {
                            configKeys.data[key] = value;
                        }
                    }
                }
            })
            .addCase(patchConfigThunk.rejected, (state, action) => {
                // Our error handling is particularly complicated here because we are using a flat form for "not-flat" values
                // This endpoint is pretty special in using dots in the way it does here.
                // Specifically the dots in these paths behave differently for our configs because configs are dealt with as a flat list, rather than using dots to refer to a nested property.
                const error = (action.payload ?? action.error) as IServerError;
                if (typeof error.errors === "object" && error !== null) {
                    const newFieldErrors: IFieldError[] = Object.values(error.errors)
                        .flat()
                        .map((fieldError) => {
                            return {
                                ...fieldError,
                                field: [fieldError.path, fieldError.field].filter(notEmpty).join("."),
                                path: undefined,
                            };
                        });

                    error.errors = indexArrayByKey(newFieldErrors, "field");
                }

                state.configPatchesByID[action.meta.arg.watchID] = {
                    status: LoadStatus.ERROR,
                    error: {
                        ...action.error,
                        ...(action.payload as any), // Kludge because our redux toolkit error handling is infuriating.
                    },
                };
            })
            // Machine Translation
            .addCase(getAllTranslationServicesThunk.pending, (state, action) => {
                state.machineTranslation.services = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(getAllTranslationServicesThunk.fulfilled, (state, action) => {
                state.machineTranslation.services = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(getAllTranslationServicesThunk.rejected, (state, action) => {
                state.machineTranslation.services = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(putTranslationServiceThunk.pending, (state, action) => {
                state.machineTranslation.put = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(putTranslationServiceThunk.fulfilled, (state, action) => {
                state.machineTranslation.put = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
                // Replace the service list with the updated value from the put response
                state.machineTranslation.services.data = state.machineTranslation.services.data?.map((service) => {
                    if (service.type === action.payload.type) {
                        return { ...service, ...action.payload };
                    }
                    return service;
                });
            })
            .addCase(putTranslationServiceThunk.rejected, (state, action) => {
                state.machineTranslation.put = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(updateConfigsLocal, (state, action) => {
                for (const [key, value] of Object.entries(action.payload)) {
                    Object.values(state.configsByLookupKey).forEach((config) => {
                        if (!config.data) {
                            return;
                        }

                        if (!(key in config.data)) {
                            return;
                        }

                        config.data[key] = value;
                    });
                }
            })
            .addCase(getAddonsByTypeThunk.pending, (state, action) => {
                state.addons[action.meta.arg.values] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(getAddonsByTypeThunk.fulfilled, (state, action) => {
                state.addons[action.meta.arg.values] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(getAddonsByTypeThunk.rejected, (state, action) => {
                state.addons[action.meta.arg.values] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(patchAddonByIdThunk.pending, (state, action) => {
                state.addonPatchByID[action.meta.arg.newConfig.type] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(patchAddonByIdThunk.fulfilled, (state, action) => {
                const type = action.meta.arg.newConfig.type;
                state.addonPatchByID[type] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };

                // Replace the updated values from the response in the addons list
                state.addons[type].data = state.addons[type].data?.map((item) => {
                    if (item.addonID === action.payload[0].addonID) {
                        return {
                            ...item,
                            ...action.payload[0], // Response returns an array
                        };
                    }
                    return item;
                });
            })
            .addCase(patchAddonByIdThunk.rejected, (state, action) => {
                state.addonPatchByID[action.meta.arg.newConfig.type] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(getAvailableLocalesThunk.pending, (state, action) => {
                state.locales = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(getAvailableLocalesThunk.fulfilled, (state, action) => {
                state.locales = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(getAvailableLocalesThunk.rejected, (state, action) => {
                state.locales = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(getServicesByLocaleThunk.pending, (state, action) => {
                state.localeTranslationService[action.meta.arg.localeID] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(getServicesByLocaleThunk.fulfilled, (state, action) => {
                state.localeTranslationService[action.meta.arg.localeID] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(getServicesByLocaleThunk.rejected, (state, action) => {
                state.localeTranslationService[action.meta.arg.localeID] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            });
    },
});

const store = configureStore({ reducer: { [configSlice.name]: configSlice.reducer } });
export type ConfigDispatch = typeof store.dispatch;
export const useConfigDispatch = () => useDispatch<typeof store.dispatch>();
