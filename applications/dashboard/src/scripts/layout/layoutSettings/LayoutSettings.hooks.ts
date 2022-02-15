/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ILayout, ILayoutsStoreState, ILayoutViewQuery } from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { useLayoutDispatch } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import { LoadStatus } from "@library/@types/api/core";
import { useCallback, useEffect, useMemo } from "react";
import { useSelector } from "react-redux";
import { bindActionCreators } from "redux";
import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import isEmpty from "lodash/isEmpty";

export function useLayoutsActions() {
    const dispatch = useLayoutDispatch();
    return useMemo(() => bindActionCreators(layoutActions, dispatch), [dispatch]);
}

export function useLayouts() {
    const { fetchAllLayouts } = useLayoutsActions();
    const layoutsListStatus = useSelector((state: ILayoutsStoreState) => state.layoutSettings.layoutsListStatus);
    const layoutsByViewType = useSelector((state: ILayoutsStoreState) => state.layoutSettings.layoutsByViewType);

    useEffect(() => {
        if (layoutsListStatus.status === LoadStatus.PENDING) {
            fetchAllLayouts();
        }
    }, [layoutsListStatus, fetchAllLayouts]);

    return {
        isLoading: [LoadStatus.PENDING, LoadStatus.LOADING].includes(layoutsListStatus.status),
        error: layoutsListStatus.status === LoadStatus.ERROR && layoutsListStatus.error,
        layoutsByViewType,
    };
}

export function useLayout(layoutID: ILayout["layoutID"]) {
    const { fetchLayout } = useLayoutsActions();
    const layout = useSelector((state: ILayoutsStoreState) => state.layoutSettings.layoutsByID[layoutID]);

    useEffect(() => {
        if (!layout) {
            fetchLayout(layoutID);
        }
    }, [fetchLayout, layout, layoutID]);

    return layout ?? { status: LoadStatus.PENDING };
}

export function usePutLayoutView(layoutID: ILayout["layoutID"]) {
    const { putLayoutView } = useLayoutsActions();

    return useCallback(
        (query: Omit<ILayoutViewQuery, "layoutID">) => {
            putLayoutView({
                layoutID,
                ...query,
            });
        },

        [putLayoutView, layoutID],
    );
}
