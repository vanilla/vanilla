/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import React from "react";
import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import {
    getLayoutsByViewType,
    useLayoutDispatch,
    useLayoutSelector,
} from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import {
    ILayoutDetails,
    ILayoutEdit,
    ILayoutsStoreState,
    ILayoutView,
    LayoutViewFragment,
    LayoutViewType,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import { updateConfigsLocal } from "@library/config/configActions";
import { useToast } from "@library/features/toaster/ToastContext";
import { t } from "@vanilla/i18n";
import { useThrowError } from "@vanilla/react-utils";
import { RecordID } from "@vanilla/utils";
import { useCallback, useEffect, useMemo } from "react";
import { useSelector } from "react-redux";
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

export function usePutLayoutViews(layout: ILayoutDetails) {
    const { layoutID, layoutViewType } = layout;
    const dispatch = useLayoutDispatch();

    return async (layoutViews: LayoutViewFragment[]) => {
        await dispatch(
            layoutActions.putLayoutViews({
                layoutID,
                layoutViews,
            }),
        ).unwrap();
        dispatch(
            updateConfigsLocal({
                [`customLayout.${layoutViewType}`]: true,
            }),
        );
    };
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

export function useLegacyLayoutView(layoutViewType: LayoutViewType, legacyViewValueConfig?: string) {
    const { putLayoutLegacyView } = useLayoutsActions();
    const toastContext = useToast();
    const state = useSelector((state: ILayoutsStoreState) => {
        return (
            state.layoutSettings.legacyStatusesByViewType[layoutViewType] ?? {
                status: LoadStatus.PENDING,
            }
        );
    });

    useEffect(() => {
        if (state.error) {
            toastContext.addToast({
                dismissible: true,
                body: (
                    <>
                        {t("Error apply layout.")} {state.error.message}
                    </>
                ),
            });
        }
    }, [state.error]);

    const putLegacyView = useCallback(
        (legacyViewValue?: string) => {
            putLayoutLegacyView({ layoutViewType, legacyViewValue, legacyViewValueConfig });
        },
        [putLayoutLegacyView],
    );

    const isSubmitLoading = state.status === LoadStatus.LOADING;

    return {
        isSubmitLoading,
        putLegacyView,
    };
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

    const catalogLoadable = catalogStatus[layoutViewType ?? ""] ?? { status: LoadStatus.PENDING };
    const throwError = useThrowError();

    useEffect(() => {
        if (catalogLoadable.status === LoadStatus.ERROR) {
            throwError(catalogLoadable.error);
        }
    }, [catalogLoadable]);

    return catalog;
}
