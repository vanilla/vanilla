/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IAuthenticator, IAuthenticatorIDList } from "@oauth2/AuthenticatorTypes";
import { IServerError, Loadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";
import {
    deleteAuthenticator,
    getAllAuthenticators,
    getAuthenticator,
    patchAuthenticator,
    postAuthenticator,
} from "@oauth2/AuthenticatorActions";

export interface IAuthenticatorDeleteState {
    authenticatorID?: number;
    error?: IServerError;
    status?: LoadStatus;
}
export interface IAuthenticatorsState {
    authenticatorsByID: Record<NonNullable<IAuthenticator["authenticatorID"]>, IAuthenticator>;
    authenticatorIDsByHash: Record<string, Loadable<IAuthenticatorIDList>>;
    deleteState: IAuthenticatorDeleteState;
}

export const INITIAL_AUTHENTICATORS_STATE: IAuthenticatorsState = {
    authenticatorsByID: {},
    authenticatorIDsByHash: {},
    deleteState: {},
};

export const authenticatorsSlice = createSlice({
    name: "authenticators",
    initialState: INITIAL_AUTHENTICATORS_STATE,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(getAllAuthenticators.pending, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.authenticatorIDsByHash[paramHash] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(getAllAuthenticators.fulfilled, (state, action) => {
                const hash = stableObjectHash(action.meta.arg);
                const { items, pagination } = action.payload;
                state.authenticatorIDsByHash[hash] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        items: items.map((authenticator) => authenticator.authenticatorID!),
                        pagination,
                    },
                };
                items.forEach((authenticator) => {
                    state.authenticatorsByID[authenticator.authenticatorID!] = authenticator;
                });
            })
            .addCase(getAllAuthenticators.rejected, (state, action) => {
                const paramHash = stableObjectHash(action.meta.arg);
                state.authenticatorIDsByHash[paramHash] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(getAuthenticator.fulfilled, (state, action) => {
                const authenticator = action.payload;
                state.authenticatorsByID[authenticator.authenticatorID!] = authenticator;
            })
            .addCase(postAuthenticator.fulfilled, (state, action) => {
                const authenticator = action.payload;
                state.authenticatorsByID[authenticator.authenticatorID!] = authenticator;
                state.authenticatorsByID = {};
                state.authenticatorIDsByHash = {};
            })
            .addCase(patchAuthenticator.fulfilled, (state, action) => {
                const authenticator = action.payload;
                if (authenticator.authenticatorID) {
                    const wasDefault = state.authenticatorsByID[authenticator.authenticatorID]?.default;
                    const isDefault = authenticator.default;
                    if (!wasDefault && isDefault) {
                        Object.values(state.authenticatorsByID).forEach((authenticator) => {
                            authenticator.default = false;
                        });
                    }
                    state.authenticatorsByID[authenticator.authenticatorID] = authenticator;
                }
            })
            .addCase(deleteAuthenticator.pending, (state, action) => {
                state.deleteState = {
                    authenticatorID: action.meta.arg,
                    status: LoadStatus.LOADING,
                    error: undefined,
                };
            })
            .addCase(deleteAuthenticator.rejected, (state, action) => {
                state.deleteState = {
                    authenticatorID: action.meta.arg,
                    status: LoadStatus.ERROR,
                    error: action.payload,
                };
            })
            .addCase(deleteAuthenticator.fulfilled, (state, action) => {
                const authenticatorID = action.meta.arg;
                state.deleteState = {
                    authenticatorID,
                    status: LoadStatus.SUCCESS,
                    error: undefined,
                };
                delete state.authenticatorsByID[action.meta.arg];

                Object.keys(state.authenticatorIDsByHash).forEach((paramHash) => {
                    if (state.authenticatorIDsByHash[paramHash].data !== undefined) {
                        state.authenticatorIDsByHash[paramHash].data!.items = state.authenticatorIDsByHash[
                            paramHash
                        ].data!.items.filter((key) => key !== action.meta.arg);
                    }
                });
            });
    },
});

const dispatch = configureStore(authenticatorsSlice).dispatch;
export const useAuthenticatorsDispatch = () => useDispatch<typeof dispatch>();
export const useAuthenticatorsSelector: TypedUseSelectorHook<{ authenticators: IAuthenticatorsState }> = useSelector;
