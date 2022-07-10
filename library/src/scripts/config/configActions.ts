import apiv2 from "@library/apiv2";
import { createAsyncThunk } from "@reduxjs/toolkit";

export const getConfigsByKeyThunk = createAsyncThunk("@@config/get", async (configKeys: string[]) => {
    const response = await apiv2.get("/config", {
        params: {
            select: configKeys.join(","),
        },
    });
    return response.data;
});

export const patchConfigThunk = createAsyncThunk(
    "@@config/patch",
    async (params: { values: Record<string, any>; watchID: string }) => {
        const response = await apiv2.patch("/config", params.values);
        return response.data;
    },
);

export const getAllTranslationServicesThunk = createAsyncThunk("@@config/get-translation-services", async () => {
    const response = await apiv2.get(`/translation-services`, {});

    return response.data;
});

export const putTranslationServiceThunk = createAsyncThunk(
    "@@config/put-translation-service",
    async (params: { values: string; newConfig: any }) => {
        const response = await apiv2.put(`/translation-services/${params.values}`, params.newConfig);
        return response.data;
    },
);

export const getAddonsByTypeThunk = createAsyncThunk("@@config/get-addons", async (params: { values: string }) => {
    const response = await apiv2.get(`/addons?type=${params.values}`, {});
    return response.data;
});

export const patchAddonByIdThunk = createAsyncThunk(
    "@@config/patch-addon",
    async (params: { values: string; newConfig: { enabled: boolean; type: string } }) => {
        const response = await apiv2.patch(`addons/${params.values}`, params.newConfig);
        return response.data;
    },
);

export const getAvailableLocalesThunk = createAsyncThunk("@@config/get-available-locales", async () => {
    const response = await apiv2.get(`/locales`, {});
    return response.data;
});

export const getServicesByLocaleThunk = createAsyncThunk(
    "@@config/get-service-by-locale",
    async (params: { localeID: string }) => {
        const response = await apiv2.get(`/locales/${params.localeID}`, {});
        return response.data;
    },
);

export const patchServicesByLocaleThunk = createAsyncThunk(
    "@@config/patch-service-by-locale",
    async (params: { localeID: string; service: string }) => {
        const response = await apiv2.patch(`/locales/${params.localeID}`, {
            translationService: params.service,
        });
        return response.data;
    },
);
