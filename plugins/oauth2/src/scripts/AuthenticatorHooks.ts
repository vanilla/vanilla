import React, { useCallback, useEffect, useMemo, useState } from "react";
import apiv2 from "@library/apiv2";
import { AuthenticatorActions, useAuthenticatorActions } from "@oauth2/AuthenticatorActions";
import {
    IAuthenticator,
    IAuthenticatorFormHook,
    IAuthenticatorList,
    IAuthenticatorStore,
    IGetAllAuthenticatorsParams,
} from "@oauth2/AuthenticatorTypes";
import { useSelector, useDispatch } from "react-redux";
import { ThunkDispatch } from "redux-thunk";
import { AnyAction } from "redux";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";

export function useAuthenticators(params?: IGetAllAuthenticatorsParams): ILoadable<IAuthenticatorList> {
    const hash = params ? stableObjectHash(params) : "";
    const authenticatorIDsByHash = useSelector(
        (state: IAuthenticatorStore) => state.authenticators.authenticatorIDsByHash,
    );
    const authenticatorsByID = useSelector((state: IAuthenticatorStore) => state.authenticators.authenticatorsByID);
    const authenticatorIDs = authenticatorIDsByHash[hash];
    const authenticators = useMemo<IAuthenticatorList | undefined>(() => {
        if (!authenticatorIDs || authenticatorIDs.status !== LoadStatus.SUCCESS) {
            return undefined;
        }
        return {
            items: authenticatorIDs.data!.items.map((id) => authenticatorsByID[id]),
            pagination: authenticatorIDs.data!.pagination,
        };
    }, [authenticatorIDs, authenticatorsByID]);
    const { getAll } = useAuthenticatorActions();

    useEffect(() => {
        if (params && !authenticatorIDs) {
            void getAll(params!);
        }
    }, [getAll, authenticatorIDs, params]);

    return {
        ...authenticatorIDs,
        data: authenticators,
    };
}

export function useAuthenticatorForm(authenticatorID: number | undefined): IAuthenticatorFormHook {
    const { initForm, updateForm, clearForm } = useAuthenticatorActions();

    const dispatch = useDispatch<ThunkDispatch<IAuthenticatorStore, any, AnyAction>>();
    const form = useSelector((state: IAuthenticatorStore) => state.authenticators.form);
    const fieldsError = form.error?.response.data?.errors;

    useEffect(() => {
        initForm(authenticatorID);
    }, [authenticatorID, initForm]);

    const update = useCallback(
        (data: Partial<IAuthenticator>) => {
            updateForm(data);
        },
        [updateForm],
    );

    const save = useCallback(
        () =>
            dispatch((dispatch, getState) => {
                const { data } = getState().authenticators.form;
                return new AuthenticatorActions(dispatch, apiv2).saveForm(data);
            }),
        [dispatch],
    );

    return {
        form,
        update,
        save,
        fieldsError,
    };
}

export function useDeleteAuthenticator() {
    const deleteState = useSelector((state: IAuthenticatorStore) => state.authenticators.deleteState);
    const actions = useAuthenticatorActions();

    const deleteAuthenticator = useCallback(
        (authenticatorID: number) => {
            actions.deleteAuthenticator(authenticatorID);
        },
        [actions],
    );

    return {
        deleteState,
        deleteAuthenticator,
    };
}
