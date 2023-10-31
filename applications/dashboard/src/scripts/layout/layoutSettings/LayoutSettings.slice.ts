/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { t } from "@vanilla/i18n";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";
import {
    clearLayoutDraft,
    deleteLayout,
    fetchLayoutCatalogByViewType,
    fetchLayoutJson,
    initializeLayoutDraft,
    persistLayoutDraft,
    updateLayoutDraft,
} from "./LayoutSettings.actions";
import { ILayoutsState, INITIAL_LAYOUTS_STATE, LayoutViewType } from "./LayoutSettings.types";

export const layoutSettingsSlice = createSlice({
    name: "layoutSettings",
    initialState: INITIAL_LAYOUTS_STATE,
    reducers: {},
    extraReducers: (builder) => {
        builder
            .addCase(fetchLayoutJson.pending, (state, action) => {
                state.layoutJsonsByLayoutID[action.meta.arg] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(fetchLayoutJson.rejected, (state, action) => {
                state.layoutJsonsByLayoutID[action.meta.arg] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(fetchLayoutJson.fulfilled, (state, action) => {
                state.layoutJsonsByLayoutID[action.meta.arg] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
            })
            .addCase(persistLayoutDraft.fulfilled, (state, action) => {
                state.layoutJsonsByLayoutID[action.payload.layoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...(state.layoutJsonsByLayoutID[action.payload.layoutID]?.data ?? {}),
                        ...action.payload,
                    },
                };

                state.layoutJsonsByLayoutID[action.payload.layoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...(state.layoutJsonsByLayoutID[action.payload.layoutID]?.data ?? {}),
                        ...action.payload,
                    },
                };
            })
            .addCase(deleteLayout.fulfilled, (state, action) => {
                const { layoutID } = action.meta.arg;
                delete state.layoutsByID[layoutID];
                delete state.layoutJsonsByLayoutID[layoutID];
            })
            .addCase(initializeLayoutDraft, (state, action) => {
                const { initialLayout = {} } = action.payload;

                state.layoutDraft = {
                    name: t("My Layout"),
                    layoutViewType: initialLayout.layoutViewType as LayoutViewType,
                    layout: [],
                    ...initialLayout,
                };
            })
            .addCase(clearLayoutDraft, (state, action) => {
                state.layoutDraft = INITIAL_LAYOUTS_STATE.layoutDraft;
            })
            .addCase(updateLayoutDraft, (state, action) => {
                if (state.layoutDraft) {
                    state.layoutDraft = {
                        ...state.layoutDraft,
                        ...action.payload,
                    };
                }
            })
            .addCase(fetchLayoutCatalogByViewType.pending, (state, action) => {
                state.catalogStatusByViewType[action.meta.arg] = { status: LoadStatus.LOADING };
            })
            .addCase(fetchLayoutCatalogByViewType.fulfilled, (state, action) => {
                state.catalogStatusByViewType[action.meta.arg] = { status: LoadStatus.SUCCESS, data: {} };
                state.catalogByViewType[action.meta.arg] = action.payload as any; // TODO: Fix once we upgrade immer.
            })
            .addCase(fetchLayoutCatalogByViewType.rejected, (state, action) => {
                state.catalogStatusByViewType[action.meta.arg] = { status: LoadStatus.ERROR, error: action.error };
            });
    },
});

const store = configureStore({ reducer: { [layoutSettingsSlice.name]: layoutSettingsSlice.reducer } });
export type layoutDispatch = typeof store.dispatch;
export const useLayoutDispatch = () => useDispatch<typeof store.dispatch>();
export const useLayoutSelector: TypedUseSelectorHook<{ [layoutSettingsSlice.name]: ILayoutsState }> = useSelector;
