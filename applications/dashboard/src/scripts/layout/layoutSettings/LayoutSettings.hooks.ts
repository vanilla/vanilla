/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import {
    getLayoutsByViewType,
    useLayoutDispatch,
    useLayoutSelector,
} from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import {
    ILayoutDetails,
    ILayoutEdit,
    ILayoutView,
    LayoutViewFragment,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import { RecordID } from "@vanilla/utils";
import { useCallback, useEffect, useMemo } from "react";
import { bindActionCreators } from "redux";

export function useLayoutsActions() {
    const dispatch = useLayoutDispatch();
    return useMemo(() => bindActionCreators(layoutActions, dispatch), [dispatch]);
}
export function useLayouts() {
    const { fetchAllLayouts } = useLayoutsActions();
    const layoutsListStatus = useLayoutSelector(({ layoutSettings }) => layoutSettings.layoutsListStatus);
    const layoutsByViewType = useLayoutSelector(({ layoutSettings }) => getLayoutsByViewType(layoutSettings));

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

export function useLayout(layoutID?: ILayoutDetails["layoutID"]) {
    const { fetchLayout } = useLayoutsActions();
    const layout = useLayoutSelector(({ layoutSettings }) =>
        layoutID !== undefined ? layoutSettings.layoutsByID[layoutID] : undefined,
    );

    useEffect(() => {
        if (layoutID !== undefined && !layout) {
            fetchLayout(layoutID);
        }
    }, [fetchLayout, layout, layoutID]);

    return layout ?? { status: LoadStatus.PENDING };
}

export function useLayoutJson(layoutID: ILayoutDetails["layoutID"]): Loadable<ILayoutEdit> {
    const { fetchLayoutJson } = useLayoutsActions();
    const loadable = useLayoutSelector(
        ({ layoutSettings }) =>
            layoutSettings.layoutJsonsByLayoutID[layoutID] ?? {
                status: LoadStatus.PENDING,
            },
    );

    useEffect(() => {
        if (loadable.status === LoadStatus.PENDING) {
            fetchLayoutJson(layoutID);
        }
    }, [fetchLayoutJson, loadable, layoutID]);

    return loadable;
}

export function usePutLayoutViews(layoutID: ILayoutDetails["layoutID"]) {
    const dispatch = useLayoutDispatch();

    return async (layoutViews: LayoutViewFragment[]) =>
        dispatch(
            layoutActions.putLayoutViews({
                layoutID,
                layoutViews,
            }),
        ).unwrap();
}

export function useDeleteLayout({
    layoutID,
    onSuccessBeforeDeletion,
}: {
    layoutID: ILayoutDetails["layoutID"];
    onSuccessBeforeDeletion?: () => void;
}) {
    const dispatch = useLayoutDispatch();
    return async () => dispatch(layoutActions.deleteLayout({ layoutID, onSuccessBeforeDeletion })).unwrap();
}

export function useDeleteLayoutView() {
    const { deleteLayoutView } = useLayoutsActions();

    return useCallback(
        (layoutID: ILayoutView["layoutID"]) => {
            deleteLayoutView({ layoutID: layoutID });
        },

        [deleteLayoutView],
    );
}

/**
 * Get layout catalog information.
 *
 * @param layoutID The ID of the layout.
 */
export function useCatalogForLayout(layoutID: RecordID) {
    const layout = useLayout(layoutID);
    const catalog = useLayoutCatalog(layout?.data?.layoutViewType ?? null);
    return catalog;
}

/**
 * Get layout catalogue information.
 *
 * @param layoutViewType The type of layout to use.
 */
export function useLayoutCatalog(layoutViewType: LayoutViewType | null) {
    const { fetchLayoutCatalogByViewType } = useLayoutsActions();

    const catalogs = useLayoutSelector(({ layoutSettings }) => layoutSettings.catalogByViewType);
    const catalogStatus = useLayoutSelector(({ layoutSettings }) => layoutSettings.catalogStatusByViewType);
    const catalog = useMemo(() => (layoutViewType && catalogs[layoutViewType]) ?? null, [layoutViewType, catalogs]);

    useEffect(() => {
        if (layoutViewType == null) {
            // Can't do anything.
            return;
        }

        const currentStatus = catalogStatus[layoutViewType]?.status ?? LoadStatus.PENDING;
        if (currentStatus !== LoadStatus.PENDING) {
            // We're already loading.
            return;
        }

        // Start fetching.
        fetchLayoutCatalogByViewType(layoutViewType);
    }, [fetchLayoutCatalogByViewType, catalogStatus, layoutViewType]);

    return catalog;
}
