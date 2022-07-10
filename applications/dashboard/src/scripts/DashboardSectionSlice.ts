/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { fetchDashboardSections } from "@dashboard/DashboardSectionActions";
import { IDashboardSectionInitialState, IDashboardSectionState } from "@dashboard/DashboardSectionType";
import { LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";

export const dashboardSectionSlice = createSlice({
    name: "dashboard",
    initialState: IDashboardSectionInitialState,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchDashboardSections.pending, (state, action) => {
                state.dashboardSections.status = LoadStatus.LOADING;
            })
            .addCase(fetchDashboardSections.fulfilled, (state, action) => {
                state.dashboardSections.status = LoadStatus.SUCCESS;
                state.dashboardSections.data = action.payload.result;
            })
            .addCase(fetchDashboardSections.rejected, (state, action) => {
                state.dashboardSections.status = LoadStatus.ERROR;
                state.dashboardSections.error = action.error;
            });
    },
});

const store = configureStore({ reducer: { [dashboardSectionSlice.name]: dashboardSectionSlice.reducer } });
export type DashboardSectionDispatch = typeof store.dispatch;
export const useDashboardSectionDispatch = () => useDispatch<typeof store.dispatch>();
export const useDashboardSectionSelector: TypedUseSelectorHook<{
    [dashboardSectionSlice.name]: IDashboardSectionState;
}> = useSelector;
