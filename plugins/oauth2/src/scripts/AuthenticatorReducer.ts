/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import {
    IAuthenticatorState,
    INITIAL_AUTHENTICATOR_FORM_STATE,
    INITIAL_AUTHENTICATOR_STATE,
} from "@oauth2/AuthenticatorTypes";
import { AuthenticatorActions } from "@oauth2/AuthenticatorActions";
import produce from "immer";
import { reducerWithInitialState } from "typescript-fsa-reducers";
import { LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";

export const AuthenticatorReducer = produce(
    reducerWithInitialState<IAuthenticatorState>(INITIAL_AUTHENTICATOR_STATE)
        // GET
        .case(AuthenticatorActions.getAllAuthenticatorACs.started, (state, payload) => {
            const hash = stableObjectHash(payload);
            state.authenticatorIDsByHash[hash] = {
                status: LoadStatus.LOADING,
            };
            return state;
        })
        .case(AuthenticatorActions.getAllAuthenticatorACs.failed, (state, payload) => {
            const hash = stableObjectHash(payload.params);
            state.authenticatorIDsByHash[hash] = {
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(AuthenticatorActions.getAllAuthenticatorACs.done, (state, payload) => {
            const hash = stableObjectHash(payload.params);
            const { items, pagination } = payload.result;
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
            return state;
        })
        // GET_EDIT
        .case(AuthenticatorActions.getEditAuthenticatorAC.started, (state) => {
            state.form.status = LoadStatus.LOADING;
            return state;
        })
        .case(AuthenticatorActions.getEditAuthenticatorAC.failed, (state, payload) => {
            state.form.status = LoadStatus.ERROR;
            state.form.error = payload.error;
            return state;
        })
        .case(AuthenticatorActions.getEditAuthenticatorAC.done, (state, payload) => {
            state.form.status = LoadStatus.SUCCESS;
            state.form.data = payload.result;
            return state;
        })
        // POST
        .case(AuthenticatorActions.postFormACs.started, (state, payload) => {
            state.form.status = LoadStatus.LOADING;
            return state;
        })
        .case(AuthenticatorActions.postFormACs.failed, (state, payload) => {
            state.form.status = LoadStatus.ERROR;
            state.form.error = payload.error;
            return state;
        })
        .case(AuthenticatorActions.postFormACs.done, (state, payload) => {
            state.form.status = LoadStatus.SUCCESS;
            state.authenticatorsByID = {};
            state.authenticatorIDsByHash = {};
            return state;
        })
        // PATCH
        .case(AuthenticatorActions.patchFormACs.started, (state, payload) => {
            state.form.status = LoadStatus.LOADING;
            return state;
        })
        .case(AuthenticatorActions.patchFormACs.failed, (state, payload) => {
            state.form.status = LoadStatus.ERROR;
            state.form.error = payload.error;
            return state;
        })
        .case(AuthenticatorActions.patchFormACs.done, (state, payload) => {
            state.form.status = LoadStatus.SUCCESS;
            const authenticator = payload.result;
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
            return state;
        })
        // UPDATE_FORM
        .case(AuthenticatorActions.updateFormAC, (state, payload) => {
            state.form.data = { ...state.form.data, ...payload };
            return state;
        })
        // CLEAR_FORM
        .case(AuthenticatorActions.clearFormAC, (state) => {
            state.form = INITIAL_AUTHENTICATOR_FORM_STATE;
            delete state.form.error;
            return state;
        })
        // DELETE
        .case(AuthenticatorActions.deleteAuthenticatorACs.started, (state, payload) => {
            state.deleteState = {
                authenticatorID: payload,
                status: LoadStatus.LOADING,
                error: undefined,
            };
            return state;
        })
        .case(AuthenticatorActions.deleteAuthenticatorACs.failed, (state, payload) => {
            state.deleteState = {
                authenticatorID: payload.params,
                status: LoadStatus.ERROR,
                error: payload.error,
            };
            return state;
        })
        .case(AuthenticatorActions.deleteAuthenticatorACs.done, (state, payload) => {
            state.deleteState = {
                authenticatorID: payload.params,
                status: LoadStatus.SUCCESS,
                error: undefined,
            };
            state.authenticatorsByID = {};
            state.authenticatorIDsByHash = {};
            return state;
        }),
);
