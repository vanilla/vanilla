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

        let catalog = response.data;

        // FIXME: https://higherlogic.atlassian.net/browse/VNLA-7689: Allow registering widgets to specific layout view types
        // This is a temporary fix to remove Article-specific widgets from the catalog on non-Article layouts.
        if (layoutViewType && catalog?.widgets) {
            if (!["guideArticle", "helpCenterArticle"].includes(layoutViewType)) {
                delete catalog.widgets["react.article-related-articles"];
                delete catalog.widgets["react.article-reactions"];
                delete catalog.widgets["react.article-toc"];
            }
            if (layoutViewType !== "guideArticle") {
                delete catalog.widgets["react.guide-pager"];
            }
        }

        return catalog;
    },
);
