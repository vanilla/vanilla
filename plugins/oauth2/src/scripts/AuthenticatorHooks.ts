import { useCallback, useEffect, useMemo, useState } from "react";

import {
    IAuthenticationRequest,
    IAuthenticationRequestPrompt,
    IAuthenticator,
    IAuthenticatorList,
    IAuthenticatorUserMappings,
    IGetAllAuthenticatorsParams,
} from "@oauth2/AuthenticatorTypes";
import { useAuthenticatorsDispatch, useAuthenticatorsSelector } from "@oauth2/AuthenticatorReducer";
import { ILoadable, LoadStatus } from "@library/@types/api/core";
import { stableObjectHash } from "@vanilla/utils";
import {
    deleteAuthenticator,
    getAllAuthenticators,
    getAuthenticator,
    patchAuthenticator,
    postAuthenticator,
    setAuthenticatorActive,
} from "@oauth2/AuthenticatorActions";

export function useAuthenticators(params?: IGetAllAuthenticatorsParams): ILoadable<IAuthenticatorList> {
    const dispatch = useAuthenticatorsDispatch();

    const hash = params ? stableObjectHash(params) : "";
    const authenticatorIDsByHash = useAuthenticatorsSelector(
        ({ authenticators: { authenticatorIDsByHash } }) => authenticatorIDsByHash,
    );
    const authenticatorsByID = useAuthenticatorsSelector(
        ({ authenticators: { authenticatorsByID } }) => authenticatorsByID,
    );
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

    useEffect(() => {
        if (params && !authenticatorIDs) {
            dispatch(getAllAuthenticators(params));
        }
    }, [dispatch, authenticatorIDs, params]);

    return {
        ...authenticatorIDs,
        data: authenticators,
    };
}

export const INITIAL_AUTHENTICATOR_USER_MAPPINGS: IAuthenticatorUserMappings = {
    uniqueID: "user_id",
    email: "email",
    name: "displayname",
    fullName: "name",
    photoUrl: "picture",
    roles: "roles",
};

export const INITIAL_AUTHENTICATION_REQUEST: IAuthenticationRequest = {
    scope: "",
    prompt: IAuthenticationRequestPrompt.LOGIN,
};

export const INITIAL_AUTHENTICATOR_FORM_STATE: IAuthenticator = {
    authenticatorID: undefined,
    name: "",
    clientID: "",
    secret: "",
    type: "oauth2",
    urls: {},
    userMappings: INITIAL_AUTHENTICATOR_USER_MAPPINGS,
    authenticationRequest: INITIAL_AUTHENTICATION_REQUEST,
    useBearerToken: false,
    useBasicAuthToken: false,
    postProfileRequest: false,
    allowAccessTokens: false,
    active: true,
    default: false,
    visible: true,
};

export function useEditAuthenticator(authenticatorID: IAuthenticator["authenticatorID"]) {
    const dispatch = useAuthenticatorsDispatch();

    const authenticator = useAuthenticatorsSelector(({ authenticators: { authenticatorsByID } }) =>
        authenticatorID ? authenticatorsByID[authenticatorID] : undefined,
    );

    useEffect(() => {
        if (authenticatorID) {
            dispatch(getAuthenticator(authenticatorID));
        }
    }, [dispatch, authenticatorID]);

    const submitForm = useCallback(
        async (authenticator: IAuthenticator) =>
            dispatch(authenticatorID ? patchAuthenticator(authenticator) : postAuthenticator(authenticator)),
        [authenticatorID, dispatch],
    );

    const initialValues: IAuthenticator = {
        ...INITIAL_AUTHENTICATOR_FORM_STATE,
        ...authenticator,
    };

    return {
        initialValues,
        submitForm,
    };
}

export function useSetAuthenticatorActive() {
    const dispatch = useAuthenticatorsDispatch();

    const setActive = useCallback(
        async (authenticatorID: NonNullable<IAuthenticator["authenticatorID"]>, active: IAuthenticator["active"]) => {
            await dispatch(setAuthenticatorActive({ authenticatorID, active }));
        },
        [dispatch],
    );

    return setActive;
}

export function useDeleteAuthenticator(authenticatorID: NonNullable<IAuthenticator["authenticatorID"]>) {
    const deleteState = useAuthenticatorsSelector(({ authenticators: { deleteState } }) => deleteState);
    const dispatch = useAuthenticatorsDispatch();

    const deleteAuthenticatorCallback = useCallback(async () => {
        await dispatch(deleteAuthenticator(authenticatorID));
    }, [dispatch, authenticatorID]);

    return {
        deleteState,
        deleteAuthenticator: deleteAuthenticatorCallback,
    };
}
