/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { IApiError, ILoadable, LoadStatus } from "@library/@types/api/core";
import { lookupLayout } from "@library/features/Layout/LayoutPage.actions";
import { IHydratedLayoutSpec } from "@library/features/Layout/LayoutRenderer.types";
import { createSlice } from "@reduxjs/toolkit";
import { stableObjectHash } from "@vanilla/utils";

interface ILayoutPageState {
    layoutsByHash: Record<string, ILoadable<IHydratedLayoutSpec>>;
}

export interface ILayoutPageStoreState {
    layoutPage: ILayoutPageState;
}

export const layoutSlice = createSlice({
    name: "layoutPage",
    initialState: {
        layoutsByHash: {},
    } as ILayoutPageState,
    reducers: {},
    extraReducers: (builder) => {
        return builder
            .addCase(lookupLayout.pending, (state, action) => {
                const hash = stableObjectHash(action.meta.arg ?? {});
                state.layoutsByHash[hash] = {
                    status: LoadStatus.LOADING,
                };
                return state;
            })
            .addCase(lookupLayout.fulfilled, (state, action) => {
                const hash = stableObjectHash(action.meta.arg ?? {});
                state.layoutsByHash[hash] = {
                    status: LoadStatus.SUCCESS,
                    data: action.payload,
                };
                return state;
            })
            .addCase(lookupLayout.rejected, (state, action) => {
                const hash = stableObjectHash(action.meta.arg ?? {});
                state.layoutsByHash[hash] = {
                    status: LoadStatus.ERROR,
                    error: action.error as IApiError,
                };
                return state;
            });
    },
});
