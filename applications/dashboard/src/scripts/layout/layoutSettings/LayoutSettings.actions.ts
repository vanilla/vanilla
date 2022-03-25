/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { createAction, createAsyncThunk } from "@reduxjs/toolkit";
import { layoutDispatch } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import {
    ILayout,
    ILayoutCatalog,
    ILayoutsState,
    ILayoutView,
    ILayoutViewQuery,
    LayoutEditSchema,
    LayoutFromPostOrPatchResponse,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";

export const fetchAllLayouts = createAsyncThunk("@@layouts/fetchAllLayouts", async () => {
    const response = await apiv2.get(`/layouts?expand=true,users`, {});
    return response.data as ILayout[];
});

export const fetchLayout = createAsyncThunk("@@layouts/fetchLayout", async (layoutID: ILayout["layoutID"]) => {
    const response = await apiv2.get(`/layouts/${layoutID}?expand=true,users`, {});
    return response.data as ILayout;
});

export const fetchLayoutJson = createAsyncThunk("@@layouts/fetchLayoutJson", async (layoutID: ILayout["layoutID"]) => {
    const response = await apiv2.get<LayoutEditSchema>(`/layouts/${layoutID}/edit`, {});
    return response.data;
});

export const copyLayoutJsonToNewDraft = createAction<{
    sourceLayoutJsonID: ILayout["layoutID"];
    draftID?: ILayout["layoutID"];
}>("@@layouts/copyLayoutJsonToNewDraft");

export const createNewLayoutJsonDraft = createAction<{
    draftID: ILayout["layoutID"];
    layoutViewType: ILayout["layoutViewType"];
}>("@@layouts/createNewLayoutJsonDraft");

export const postOrPatchLayoutJsonDraft = createAsyncThunk<
    LayoutFromPostOrPatchResponse,
    Omit<LayoutEditSchema, "layoutID">,
    { state: { layoutSettings: ILayoutsState }; dispatch: layoutDispatch }
>("@@layouts/postOrPatchLayout", async (draft: LayoutEditSchema, thunkAPI) => {
    const {
        layoutSettings: {
            layoutsByID: { [draft.layoutID]: existingLayoutByThatID },
        },
    } = thunkAPI.getState();

    const response = await (existingLayoutByThatID
        ? apiv2.patch<LayoutFromPostOrPatchResponse>(`/layouts/${draft.layoutID}`, draft)
        : apiv2.post<LayoutFromPostOrPatchResponse>(`/layouts`, { ...draft, layoutID: undefined }));

    return response.data;
});

export const updateLayoutJsonDraft = createAction<{
    draftID: ILayout["layoutID"];
    modifiedDraft: LayoutEditSchema;
}>("@@layouts/updateLayoutJsonDraft");

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
