/**
 * @author Isis Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IServerError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import SimplePagerModel from "@library/navigation/SimplePagerModel";
import { IAuthenticator, IAuthenticatorList, IGetAllAuthenticatorsParams } from "@oauth2/AuthenticatorTypes";
import { createAsyncThunk } from "@reduxjs/toolkit";

export const getAllAuthenticators = createAsyncThunk<IAuthenticatorList, IGetAllAuthenticatorsParams>(
    "@@authenticators/getAll",
    async ({ page, limit = 10, type = "oauth2" }) => {
        const response = await apiv2.get(`/authenticators?page=${page}&limit=${limit}&type=${type}`, {});
        const pagination = SimplePagerModel.parseLinkHeader(response.headers["link"], "page");
        const result: IAuthenticatorList = {
            items: response.data,
            pagination,
        };
        return result;
    },
);

export const getAuthenticator = createAsyncThunk<
    IAuthenticator,
    NonNullable<IAuthenticator["authenticatorID"]>,
    { rejectValue: IServerError }
>("@@authenticators/get", async (authenticatorID) => {
    const { data } = await apiv2.get<IAuthenticator>(`/authenticators/${authenticatorID}/oauth2`);
    return data;
});

export const postAuthenticator = createAsyncThunk<IAuthenticator, IAuthenticator, { rejectValue: IServerError }>(
    "@@authenticators/post",
    async (form, { rejectWithValue }) => {
        try {
            const { data } = await apiv2.post<IAuthenticator>(`/authenticators/oauth2`, form);
            return data;
        } catch (e) {
            return rejectWithValue(e.response.data);
        }
    },
);

export const patchAuthenticator = createAsyncThunk<
    IAuthenticator,
    Partial<IAuthenticator>,
    { rejectValue: IServerError }
>("@@authenticators/patch", async (form, { rejectWithValue }) => {
    const { authenticatorID, ...params } = form;
    try {
        const response = await apiv2.patch<IAuthenticator>(`/authenticators/${authenticatorID}`, params);
        switch (response.data.type) {
            case "oauth2": {
                const oauth2Response = await apiv2.patch<IAuthenticator>(
                    `/authenticators/${authenticatorID}/oauth2`,
                    params,
                );
                return oauth2Response.data;
            }
        }
    } catch (e) {
        return rejectWithValue(e.response.data);
    }
});

export const setAuthenticatorActive = createAsyncThunk<
    IAuthenticator,
    Required<Pick<IAuthenticator, "authenticatorID" | "active">>
>("@@authenticators/patch", async (authenticator) => {
    const { data } = await apiv2.patch<IAuthenticator>(
        `/authenticators/${authenticator.authenticatorID}`,
        authenticator,
    );
    return data;
});

export const deleteAuthenticator = createAsyncThunk<
    any,
    NonNullable<IAuthenticator["authenticatorID"]>,
    { rejectValue: IServerError }
>("@@authenticators/delete", async (authenticatorID, { rejectWithValue }) => {
    try {
        const { data } = await apiv2.delete(`/authenticators/${authenticatorID}`);
        return data;
    } catch (e) {
        return rejectWithValue(e.response.data);
    }
});
