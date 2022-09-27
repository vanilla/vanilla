/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    fetchProfileField,
    fetchProfileFields,
    patchProfileField,
    postProfileField,
} from "@dashboard/userProfiles/state/UserProfiles.actions";
import { ProfileField } from "@dashboard/userProfiles/types/UserProfiles.types";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { stableObjectHash } from "@vanilla/utils";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";

interface IUserProfilesState {
    profileFieldApiNamesByParamHash: Record<string, ILoadable<Array<ProfileField["apiName"]>, any>>;
    profileFieldsByApiName: Record<ProfileField["apiName"], ProfileField>;
}

const INITIAL_USER_PROFILES_STATE: IUserProfilesState = {
    profileFieldApiNamesByParamHash: {},
    profileFieldsByApiName: {},
};

export const userProfilesSlice = createSlice({
    name: "userProfiles",
    initialState: INITIAL_USER_PROFILES_STATE,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchProfileFields.pending, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.profileFieldApiNamesByParamHash[paramHash] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(fetchProfileFields.fulfilled, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.profileFieldApiNamesByParamHash[paramHash] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload.map((profileField) => profileField.apiName),
                };
                action.payload.forEach((profileField) => {
                    state.profileFieldsByApiName[profileField.apiName] = profileField;
                });
            })
            .addCase(fetchProfileFields.rejected, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.profileFieldApiNamesByParamHash[paramHash] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(fetchProfileField.fulfilled, (state, action) => {
                const apiName = action.meta.arg;
                state.profileFieldsByApiName[apiName] = action.payload;
            })
            .addCase(postProfileField.fulfilled, (state, action) => {
                const { apiName } = action.payload;
                state.profileFieldsByApiName[apiName] = action.payload;
            })
            .addCase(patchProfileField.fulfilled, (state, action) => {
                const apiName = action.payload.apiName;
                state.profileFieldsByApiName[apiName] = {
                    ...state.profileFieldsByApiName[apiName],
                    ...action.payload,
                };
            });
    },
});

const store = configureStore({ reducer: { [userProfilesSlice.name]: userProfilesSlice.reducer } });

export type userProfilesDispatch = typeof store.dispatch;
export const useUserProfilesDispatch = () => useDispatch<typeof store.dispatch>();
export const useUserProfilesSelector: TypedUseSelectorHook<{ [userProfilesSlice.name]: IUserProfilesState }> =
    useSelector;
