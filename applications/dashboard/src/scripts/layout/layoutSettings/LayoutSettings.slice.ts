/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LoadStatus } from "@library/@types/api/core";
import { configureStore, createSlice } from "@reduxjs/toolkit";
import { t } from "@vanilla/i18n";
import { notEmpty } from "@vanilla/utils";
import { TypedUseSelectorHook, useDispatch, useSelector } from "react-redux";
import {
    clearLayoutDraft,
    deleteLayout,
    fetchAllLayouts,
    fetchLayout,
    fetchLayoutCatalogByViewType,
    fetchLayoutJson,
    initializeLayoutDraft,
    persistLayoutDraft,
    putLayoutViews,
    deleteLayoutView,
    updateLayoutDraft,
    putLayoutLegacyView,
} from "./LayoutSettings.actions";
import {
    ILayoutDetails,
    ILayoutsState,
    INITIAL_LAYOUTS_STATE,
    LayoutViewType,
    LAYOUT_VIEW_TYPES,
} from "./LayoutSettings.types";

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
            .addCase(putLayoutViews.pending, (state, action) => {
                state.layoutsByID[action.meta.arg.layoutID]!.status = LoadStatus.LOADING;
            })
            .addCase(putLayoutViews.rejected, (state, action) => {
                state.layoutsByID[action.meta.arg.layoutID] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
            })
            .addCase(putLayoutViews.fulfilled, (state, action) => {
                const updatedLayoutID = action.meta.arg.layoutID;

                // update the changed record
                state.layoutsByID[updatedLayoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...(state.layoutsByID[updatedLayoutID]!.data as ILayoutDetails),
                        layoutViews: [...action.payload],
                    },
                };

                // TODO: Fix any once we upgrade immer. https://github.com/vanilla/vanilla-cloud/pull/3995
                const layoutsByViewType = getLayoutsByViewType(state as any);
                const layoutIdsToUpdate = Array.from(
                    new Set(
                        action.payload
                            .map(({ layoutViewType }) => {
                                return layoutsByViewType[layoutViewType].map(({ layoutID }) => layoutID);
                            })
                            .flat()
                            .filter((layoutID) => layoutID !== updatedLayoutID),
                    ),
                );
                // remove layoutViews with the same recordID from any other layouts.
                layoutIdsToUpdate.forEach((layoutID) => {
                    if (state.layoutsByID[layoutID]?.data) {
                        state.layoutsByID[layoutID]!.data = {
                            ...state.layoutsByID[layoutID]!.data!,
                            layoutViews: state.layoutsByID[layoutID]!.data!.layoutViews.filter(
                                ({ recordID }) =>
                                    !action.payload.some((layoutView) => layoutView.recordID === recordID),
                            ),
                        };
                    }
                });
            })
            .addCase(deleteLayoutView.fulfilled, (state, action) => {
                const { layoutID } = action.meta.arg;
                const layoutData = (state.layoutsByID[layoutID]?.data ?? {}) as ILayoutDetails;
                state.layoutsByID[action.meta.arg.layoutID] = {
                    status: LoadStatus.SUCCESS,
                    data: {
                        ...layoutData,
                        layoutViews: [],
                    },
                };
            })
            .addCase(putLayoutLegacyView.pending, (state, action) => {
                const { layoutViewType } = action.meta.arg;
                state.legacyStatusesByViewType[layoutViewType] = {
                    status: LoadStatus.LOADING,
                };
            })
            .addCase(putLayoutLegacyView.fulfilled, (state, action) => {
                const { layoutViewType } = action.meta.arg;
                state.legacyStatusesByViewType[layoutViewType] = {
                    status: LoadStatus.SUCCESS,
                    data: {},
                };
                Object.values(state.layoutsByID).forEach((layout) => {
                    if (layout?.data?.layoutViewType === layoutViewType) {
                        layout.data.layoutViews = [];
                    }
                });
            })
            .addCase(putLayoutLegacyView.rejected, (state, action) => {
                const { layoutViewType } = action.meta.arg;
                state.legacyStatusesByViewType[layoutViewType] = {
                    status: LoadStatus.ERROR,
                    error: action.error,
                };
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

export function getLayoutsByViewType(state: ILayoutsState): { [key in LayoutViewType]: ILayoutDetails[] } {
    const obj = Object.fromEntries(
        LAYOUT_VIEW_TYPES.map((viewType: ILayoutDetails["layoutViewType"]) => {
            return [
                viewType,
                Object.values(state.layoutsByID)
                    .filter((val) => val?.data?.layoutViewType === viewType)
                    .filter(notEmpty)
                    .map((loadableLayout) => loadableLayout!.data!),
            ];
        }),
    );

    return obj as { [key in LayoutViewType]: ILayoutDetails[] };
}
