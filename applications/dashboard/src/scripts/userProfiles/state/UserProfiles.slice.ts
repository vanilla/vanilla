/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    deleteProfileField,
    fetchProfileField,
    fetchProfileFields,
    fetchUserProfileFields,
    patchProfileField,
    postProfileField,
    putProfileFieldsSorts,
} from "@dashboard/userProfiles/state/UserProfiles.actions";
import { ProfileField, UserProfileFields } from "@dashboard/userProfiles/types/UserProfiles.types";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice, SerializedError } from "@reduxjs/toolkit";
import { RecordID, stableObjectHash } from "@vanilla/utils";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";

export interface IUserProfilesStoreState {
    userProfiles: IUserProfilesState;
}

export interface IUserProfilesState {
    profileFieldApiNamesByParamHash: Record<string, ILoadable<Array<ProfileField["apiName"]>, any>>;
    profileFieldsByApiName: Record<ProfileField["apiName"], ProfileField>;
    profileFieldsByUserID: Record<RecordID, ILoadable<UserProfileFields, SerializedError>>;
    deleteStatusByApiName: Record<string, ILoadable<{}, SerializedError>>;
}

const INITIAL_USER_PROFILES_STATE: IUserProfilesState = {
    profileFieldApiNamesByParamHash: {},
    profileFieldsByApiName: {},
    profileFieldsByUserID: {},
    deleteStatusByApiName: {},
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
            })
            .addCase(fetchUserProfileFields.pending, (state, action) => {
                const userID = action.meta.arg.userID;
                state.profileFieldsByUserID[userID] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(fetchUserProfileFields.fulfilled, (state, action) => {
                const userID = action.meta.arg.userID;
                state.profileFieldsByUserID[userID] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(fetchUserProfileFields.rejected, (state, action) => {
                const userID = action.meta.arg.userID;
                state.profileFieldsByUserID[userID] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(deleteProfileField.pending, (state, action) => {
                const apiName = action.meta.arg;
                state.deleteStatusByApiName[apiName] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(deleteProfileField.fulfilled, (state, action) => {
                const apiName = action.meta.arg;
                state.deleteStatusByApiName[apiName] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
                delete state.profileFieldsByApiName[apiName];

                Object.keys(state.profileFieldApiNamesByParamHash).forEach((key) => {
                    state.profileFieldApiNamesByParamHash[key].data = state.profileFieldApiNamesByParamHash[
                        key
                    ].data?.filter((name) => name !== apiName);
                });
            })
            .addCase(deleteProfileField.rejected, (state, action) => {
                const apiName = action.meta.arg;
                state.deleteStatusByApiName[apiName] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(putProfileFieldsSorts.fulfilled, (state, action) => {
                Object.entries(action.meta.arg).forEach(([apiName, sort]) => {
                    state.profileFieldsByApiName[apiName] = {
                        ...state.profileFieldsByApiName[apiName],
                        sort,
                    };
                });
            });
    },
});

const store = configureStore({ reducer: { [userProfilesSlice.name]: userProfilesSlice.reducer } });

export type userProfilesDispatch = typeof store.dispatch;
export const useUserProfilesDispatch = () => useDispatch<typeof store.dispatch>();
export const useUserProfilesSelector: TypedUseSelectorHook<{ [userProfilesSlice.name]: IUserProfilesState }> =
    useSelector;

export const useUserProfilesSelectorByID: TypedUseSelectorHook<{ [userProfilesSlice.name]: IUserProfilesState }> =
    useSelector;
