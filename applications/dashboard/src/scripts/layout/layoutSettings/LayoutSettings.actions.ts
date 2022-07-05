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
    LayoutViewFragment,
    ILayoutEdit,
    ILayoutDraft,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { IApiError } from "@library/@types/api/core";
import { updateConfigsLocal } from "@library/config/configActions";

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

        if (draft.layoutID && existingLayoutJson) {
            await thunkAPI.dispatch(fetchLayout(draft.layoutID)).unwrap();
        }

        return response.data;
    } catch (err) {
        return thunkAPI.rejectWithValue(err);
    }
});

export const putLayoutViews = createAsyncThunk(
    "@@layouts/putLayoutViews",
    async (args: { layoutID: ILayoutDetails["layoutID"]; layoutViews: LayoutViewFragment[] }) => {
        const response = await apiv2.put<ILayoutView[]>(`/layouts/${args.layoutID}/views`, [...args.layoutViews]);
        return response.data;
    },
);

export const putLayoutLegacyView = createAsyncThunk<
    any,
    {
        layoutViewType: LayoutViewType;
        legacyViewValue?: string;
        legacyViewValueConfig?: string;
    },
    { serializedErrorType: IApiError }
>("@@layouts/putLegacyView", async ({ layoutViewType, legacyViewValue, legacyViewValueConfig }, thunkAPI) => {
    try {
        const response = await apiv2.put(`/layouts/views-legacy`, {
            layoutViewType,
            legacyViewValue,
        });
        if (legacyViewValueConfig && legacyViewValue) {
            thunkAPI.dispatch(
                updateConfigsLocal({
                    [`customLayout.${layoutViewType}`]: false,
                    [legacyViewValueConfig]: legacyViewValue,
                }),
            );
        }
        return response.data;
    } catch (err) {
        return thunkAPI.rejectWithValue(err);
    }
});

export const deleteLayoutView = createAsyncThunk<
    any,
    {
        layoutID: ILayoutDetails["layoutID"];
    },
    { serializedErrorType: IApiError }
>("@@layouts/deleteLayoutView", async ({ layoutID }, thunkAPI) => {
    try {
        const response = await apiv2.delete(`/layouts/${layoutID}/views`, {});
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
