/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { ITranslationService } from "@dashboard/languages/LanguageSettingsTypes";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import {
    getConfigsByKeyThunk,
    patchConfigThunk,
    getAllTranslationServicesThunk,
    putTranslationServiceThunk,
} from "@library/config/configActions";
import { createSlice } from "@reduxjs/toolkit";
import { stableObjectHash } from "@vanilla/utils";

type ConfigValuesByKey = Record<string, any>;

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
                state.configPatchesByID[action.meta.arg.watchID] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
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
                        return action.payload;
                    }
                    return service;
                });
            })
            .addCase(putTranslationServiceThunk.rejected, (state, action) => {
                state.machineTranslation.put = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            });
    },
});
