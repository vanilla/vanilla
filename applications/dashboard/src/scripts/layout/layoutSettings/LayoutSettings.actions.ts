/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { layoutDispatch } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import {
    ILayoutCatalog,
    ILayoutDetails,
    ILayoutDraft,
    ILayoutEdit,
    ILayoutsState,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { IApiError } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { createAction, createAsyncThunk } from "@reduxjs/toolkit";

export const deleteLayout = createAsyncThunk<
    any,
    { layoutID: ILayoutDetails["layoutID"]; onSuccessBeforeDeletion?: () => void },
    { serializedErrorType: IApiError }
>("@@layouts/deleteLayout", async ({ layoutID, onSuccessBeforeDeletion }, thunkAPI) => {
    try {
        const response = await apiv2.delete(`/layouts/${layoutID}`, {});
        onSuccessBeforeDeletion?.(); //pass an onSuccessBeforeDeletion callback to do things like route changes before the reducer handles deletion
        return response.data;
    } catch (err) {
        return thunkAPI.rejectWithValue(err);
    }
});

export const fetchLayoutJson = createAsyncThunk(
    "@@layouts/fetchLayoutJson",
    async (layoutID: ILayoutDetails["layoutID"]) => {
        const response = await apiv2.get<ILayoutEdit>(`/layouts/${layoutID}/edit`, {});
        return response.data;
    },
);

export const initializeLayoutDraft = createAction<{
    initialLayout?: Partial<ILayoutEdit>;
}>("@@layouts/initializeLayoutDraft");

export const clearLayoutDraft = createAction("@@layouts/clearLayoutDraft");

export const updateLayoutDraft = createAction<Partial<ILayoutDraft>>("@@layouts/updateLayoutDraft");

export const persistLayoutDraft = createAsyncThunk<
    ILayoutEdit,
    Omit<ILayoutEdit, "layoutID">,
    { serializedErrorType: IApiError; state: { layoutSettings: ILayoutsState }; dispatch: layoutDispatch }
>("@@layouts/persistLayoutDraft", async (draft: ILayoutDraft, thunkAPI) => {
    const existingLayoutJson =
        draft.layoutID && thunkAPI.getState().layoutSettings.layoutJsonsByLayoutID[draft.layoutID];

    try {
        const response = await (existingLayoutJson
            ? apiv2.patch<ILayoutEdit>(`/layouts/${draft.layoutID}`, draft)
            : apiv2.post<ILayoutEdit>(`/layouts`, { ...draft, layoutID: undefined }));

        return response.data;
    } catch (err) {
        return thunkAPI.rejectWithValue(err);
    }
});

export const fetchLayoutCatalogByViewType = createAsyncThunk(
    "@@appearance/fetchLayoutCatalogByViewType",
    async (layoutViewType: ILayoutCatalog["layoutViewType"]) => {
        const response = await apiv2.get<ILayoutCatalog>(`/layouts/catalog`, {
            params: { layoutViewType },
        });
        return response.data;
    },
);
