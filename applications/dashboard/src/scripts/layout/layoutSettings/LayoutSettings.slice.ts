/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { fetchAllLayouts, fetchLayout, putLayoutView } from "./LayoutSettings.actions";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";
import { ILayout, ILayoutsState, INITIAL_LAYOUTS_STATE } from "./LayoutSettings.types";

export const layoutSettingsSlice = createSlice({
    name: "layoutSettings",
    initialState: INITIAL_LAYOUTS_STATE,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchAllLayouts.pending, (state, action) => {
                state.layoutsListStatus = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(fetchAllLayouts.rejected, (state, action) => {
                state.layoutsListStatus = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(fetchAllLayouts.fulfilled, (state, action) => {
                state.layoutsListStatus = {
                    status: LoadStatus.SUCCESS,
                };
                // Get unique viewTypes from payload
                const viewTypes = [...new Set(action.payload.map(({ layoutViewType }: ILayout) => layoutViewType))];
                state.layoutsByViewType = Object.fromEntries(
                    viewTypes.map((viewType: ILayout["layoutViewType"]) => {
                        return [
                            viewType,
                            action.payload.filter((layout: ILayout) => layout.layoutViewType === viewType),
                        ];
                    }),
                );
            })
            .addCase(fetchLayout.pending, (state, action) => {
                state.layoutsByID[action.meta.arg] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(fetchLayout.rejected, (state, action) => {
                state.layoutsByID[action.meta.arg] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(fetchLayout.fulfilled, (state, action) => {
                state.layoutsByID[action.meta.arg] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(putLayoutView.pending, (state, action) => {
                state.layoutsByID[action.meta.arg.layoutID]!.status = LoadStatus.LOADING;
            })
            .addCase(putLayoutView.rejected, (state, action) => {
                state.layoutsByID[action.meta.arg.layoutID] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(putLayoutView.fulfilled, (state, action) => {
                const existingLayout = state.layoutsByID[action.meta.arg.layoutID]?.data as ILayout;
                state.layoutsByID[action.meta.arg.layoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...existingLayout,
                        layoutViews: [...existingLayout.layoutViews, action.payload],
                    },
                };
            });
    },
});

const store = configureStore({ reducer: { [layoutSettingsSlice.name]: layoutSettingsSlice.reducer } });
export type layoutDispatch = typeof store.dispatch;
export const useLayoutDispatch = () => useDispatch<typeof store.dispatch>();
export const useLayoutSelector: TypedUseSelectorHook<{ [layoutSettingsSlice.name]: ILayoutsState }> = useSelector;
