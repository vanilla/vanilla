import apiv2 from "@library/apiv2";
import { createAsyncThunk, createAction } from "@reduxjs/toolkit";

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
