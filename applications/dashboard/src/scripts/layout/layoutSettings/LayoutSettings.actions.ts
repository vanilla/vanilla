/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { createAction, createAsyncThunk } from "@reduxjs/toolkit";
import { layoutDispatch } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import {
    ILayoutDetails,
    ILayoutCatalog,
    ILayoutsState,
    ILayoutView,
    ILayoutViewQuery,
    ILayoutEdit,
    ILayoutDraft,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { IApiError } from "@library/@types/api/core";

export const fetchAllLayouts = createAsyncThunk("@@layouts/fetchAllLayouts", async () => {
    const response = await apiv2.get(`/layouts?expand=true,users`, {});
    return response.data as ILayoutDetails[];
});

export const fetchLayout = createAsyncThunk(
    "@@layouts/fetchLayout",
    async (layoutID: ILayoutDetails["layoutID"], thunkApi) => {
        try {
            const response = await apiv2.get(`/layouts/${layoutID}?expand=true,users`, {});
            return response.data as ILayoutDetails;
        } catch (err) {
            return thunkApi.rejectWithValue({});
        }
    },
);

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
    const existingLayout = draft.layoutID && thunkAPI.getState().layoutSettings.layoutsByID[draft.layoutID];

    try {
        const response = await (existingLayout
            ? apiv2.patch<ILayoutEdit>(`/layouts/${draft.layoutID}`, draft)
            : apiv2.post<ILayoutEdit>(`/layouts`, { ...draft, layoutID: undefined }));

        if (draft.layoutID && existingLayout) {
            await thunkAPI.dispatch(fetchLayout(draft.layoutID)).unwrap();
        }

        return response.data;
    } catch (err) {
        return thunkAPI.rejectWithValue(err);
    }
});

export const putLayoutView = createAsyncThunk("@@layouts/putLayoutView", async (query: ILayoutViewQuery) => {
    const response = await apiv2.put(`/layouts/${query.layoutID}/views`, {
        recordType: query.recordType,
        recordID: query.recordID,
    });
    return response.data as ILayoutView;
});

export const fetchLayoutCatalogByViewType = createAsyncThunk(
    "@@appearance/fetchLayoutCatalogByViewType",
    async (layoutViewType: ILayoutCatalog["layoutViewType"]) => {
        const response = await apiv2.get(`/layouts/catalog`, { params: { layoutViewType: layoutViewType } });
        return response.data as ILayoutCatalog;
    },
);
