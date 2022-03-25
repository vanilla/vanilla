/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import {
    createNewLayoutJsonDraft,
    fetchAllLayouts,
    fetchLayout,
    putLayoutView,
    fetchLayoutJson,
    copyLayoutJsonToNewDraft,
    postOrPatchLayoutJsonDraft,
    updateLayoutJsonDraft,
    fetchLayoutCatalogByViewType,
} from "./LayoutSettings.actions";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";
import { createLayoutJsonDraft } from "@dashboard/layout/layoutSettings/utils";
import {
    ILayout,
    ILayoutsState,
    INITIAL_LAYOUTS_STATE,
    LayoutViewType,
    LAYOUT_VIEW_TYPES,
} from "./LayoutSettings.types";
import { notEmpty } from "@vanilla/utils";

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
                action.payload.forEach((layout) => {
                    state.layoutsByID[layout.layoutID] = {
                        status: LoadStatus.SUCCESS,
                        data: layout,
                    };
                });
                state.layoutsListStatus = {
                    status: LoadStatus.SUCCESS,
                };
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
            .addCase(postOrPatchLayoutJsonDraft.fulfilled, (state, action) => {
                const layout = action.payload;

                const layoutJson = action.payload.layout!;

                state.layoutJsonsByLayoutID[layout.layoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        layoutID: layout.layoutID,
                        name: layout.name,
                        layoutViewType: layout.layoutViewType,
                        layout: layoutJson,
                    },
                };

                state.layoutsByID[layout.layoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...(state.layoutsByID[layout.layoutID]?.data ?? {}),
                        ...layout,
                    },
                };
            })
            .addCase(copyLayoutJsonToNewDraft, (state, action) => {
                const { sourceLayoutJsonID, draftID } = action.payload;
                const sourceLayoutJson = state.layoutJsonsByLayoutID[sourceLayoutJsonID];
                const newDraftID = draftID ?? sourceLayoutJsonID;
                if (sourceLayoutJson?.data) {
                    state.layoutJsonDraftsByID[newDraftID] = createLayoutJsonDraft({
                        ...sourceLayoutJson?.data,
                    });
                }
            })
            .addCase(createNewLayoutJsonDraft, (state, action) => {
                const { draftID, layoutViewType } = action.payload;
                state.layoutJsonDraftsByID[draftID] = createLayoutJsonDraft({ layoutViewType });
            })
            .addCase(updateLayoutJsonDraft, (state, action) => {
                state.layoutJsonDraftsByID[action.payload.draftID] = action.payload.modifiedDraft;
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
                        layoutViews: [...(existingLayout.layoutViews ?? []), action.payload],
                    },
                };
                //remove the layoutView from other layouts if applied
                const layoutsByViewType = getLayoutsByViewType(state as any); // TODO: Fix any once we upgrade immer.
                const layoutsWithSameViewType = layoutsByViewType[action.payload.layoutViewType];
                layoutsWithSameViewType.forEach((layout) => {
                    if (layout.layoutViews.length) {
                        if (layout.layoutID != action.payload.layoutID) {
                            const filteredLayoutViews = layout.layoutViews.filter(
                                (layoutView) =>
                                    layoutView.recordType !== action.payload.recordType &&
                                    layoutView.recordID !== action.payload.recordID,
                            );

                            state.layoutsByID[layout.layoutID]!.data = {
                                ...layout,
                                layoutViews: filteredLayoutViews,
                            };
                        }
                    }
                });
            })
            .addCase(fetchLayoutCatalogByViewType.pending, (state, action) => {
                state.catalogStatusByViewType[action.meta.arg] = { status: LoadStatus.LOADING };
            })
            .addCase(fetchLayoutCatalogByViewType.fulfilled, (state, action) => {
                state.catalogStatusByViewType[action.meta.arg] = { status: LoadStatus.SUCCESS };
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

export function getLayoutJsonDraftByID(state: ILayoutsState, draftID: keyof ILayoutsState["layoutJsonDraftsByID"]) {
    return state.layoutJsonDraftsByID[draftID];
}

export function getLayoutJsonByLayoutID(state: ILayoutsState, layoutID: keyof ILayoutsState["layoutJsonsByLayoutID"]) {
    return state.layoutJsonsByLayoutID[layoutID];
}

export function getLayoutsByViewType(state: ILayoutsState): { [key in LayoutViewType]: ILayout[] } {
    const obj = Object.fromEntries(
        LAYOUT_VIEW_TYPES.map((viewType: ILayout["layoutViewType"]) => {
            return [
                viewType,
                Object.values(state.layoutsByID)
                    .filter(
                        (val) =>
                            val?.status === LoadStatus.SUCCESS && !!val.data && val.data.layoutViewType === viewType,
                    )
                    .filter(notEmpty)
                    .map((loadableLayout) => loadableLayout!.data!),
            ];
        }),
    );

    return obj as { [key in LayoutViewType]: ILayout[] };
}
