/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import apiv2 from "@library/apiv2";
import { createAsyncThunk } from "@reduxjs/toolkit";
import { ILayout, ILayoutView, ILayoutViewQuery } from "@dashboard/layout/layoutSettings/LayoutSettings.types";

export const fetchAllLayouts = createAsyncThunk("@@dashboard/fetchAllLayouts", async () => {
    const response = await apiv2.get(`/layouts`, {});
    return response.data as ILayout[];
});

export const fetchLayout = createAsyncThunk("@@dashboard/fetchLayout", async (layoutID: ILayout["layoutID"]) => {
    const response = await apiv2.get(`/layouts/${layoutID}?expand=true`, {});
    return response.data as ILayout;
});

export const putLayoutView = createAsyncThunk("@@dashboard/putLayoutView", async (query: ILayoutViewQuery) => {
    const response = await apiv2.put(`/layouts/${query.layoutID}/views`, {
        recordType: query.recordType,
        recordID: query.recordID,
    });
    return response.data as ILayoutView;
});
