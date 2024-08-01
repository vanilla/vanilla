/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { LayoutOverviewRoute, LegacyLayoutsRoute } from "@dashboard/appearance/routes/appearanceRoutes";
import * as layoutActions from "@dashboard/layout/layoutSettings/LayoutSettings.actions";
import { useLayoutDispatch, useLayoutSelector } from "@dashboard/layout/layoutSettings/LayoutSettings.slice";
import {
    ILayoutDetails,
    ILayoutEdit,
    LayoutRecordType,
    LayoutViewFragment,
    LayoutViewType,
    LAYOUT_VIEW_TYPES,
} from "@dashboard/layout/layoutSettings/LayoutSettings.types";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { IError } from "@library/errorPages/CoreErrorMessages";
import { getRelativeUrl } from "@library/utility/appUtils";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { useThrowError } from "@vanilla/react-utils";
import { RecordID, spaceshipCompare } from "@vanilla/utils";
import { useEffect, useMemo } from "react";
import { useHistory } from "react-router";
import { bindActionCreators } from "redux";

export function useLayoutsActions() {
    const dispatch = useLayoutDispatch();
    return useMemo(() => bindActionCreators(layoutActions, dispatch), [dispatch]);
}

export function sliceLayoutsByViewType(layouts: ILayoutDetails[]): { [key in LayoutViewType]: ILayoutDetails[] } {
    const obj = Object.fromEntries(
        LAYOUT_VIEW_TYPES.map((viewType: ILayoutDetails["layoutViewType"]) => {
            return [
                viewType,
                Object.values(layouts)
                    .filter((layout) => layout.layoutViewType === viewType)
                    .sort((a, b) => {
                        return spaceshipCompare(a.dateInserted, b.dateInserted);
                    }),
            ];
        }),
    );

    return obj as { [key in LayoutViewType]: ILayoutDetails[] };
}

export function useLayoutsQuery() {
    const layoutsQuery = useQuery({
        queryFn: async () => {
            const response = await apiv2.get("/layouts", {
                params: {
                    expand: "true,users",
                },
            });
            return response.data;
        },
        queryKey: ["layouts"],
    });

    return layoutsQuery;
}

export function useLayoutQuery(layoutID?: ILayoutDetails["layoutID"]) {
    const layoutQuery = useQuery<{}, IError, ILayoutDetails>({
        queryFn: async () => {
            const response = await apiv2.get(`/layouts/${layoutID}?expand=true,users`, {});
            return response.data;
        },
        queryKey: ["layouts", "overview", layoutID],
        enabled: layoutID != null,
    });

    return layoutQuery;
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

export function useLayoutViewMutation(layout: ILayoutDetails) {
    const { layoutID } = layout;
    const queryClient = useQueryClient();
    const mutation = useMutation({
        mutationFn: async (layoutViews: LayoutViewFragment[]) => {
            const response = await apiv2.put(`/layouts/${layoutID}/views`, layoutViews);
            return response.data;
        },
        mutationKey: ["layoutView", layoutID],
        onSuccess: () => {
            queryClient.invalidateQueries(["layouts"]);
        },
    });
    return mutation;
}

export function useDeleteLayoutMutation(layout: ILayoutDetails) {
    const { layoutID } = layout;

    const history = useHistory();
    const queryClient = useQueryClient();
    const mutation = useMutation({
        mutationFn: async () => {
            const response = await apiv2.delete(`/layouts/${layoutID}`);
            return response.data;
        },
        mutationKey: ["layouts", "delete", layoutID],
        onSuccess: () => {
            queryClient.invalidateQueries(["layouts"]);

            // If we're on the page of this layout, redirect to the settings page
            if (LayoutOverviewRoute.url(layout).includes(history.location.pathname)) {
                history.replace(getRelativeUrl(LegacyLayoutsRoute.url(layout.layoutViewType)));
            }
        },
    });
    return mutation;
}

/**
 * Get layout catalog information.
 *
 * @param layoutID The ID of the layout.
 */
export function useCatalogForLayout(layoutID: RecordID) {
    const layout = useLayoutQuery(layoutID);
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

export function getAllowedRecordTypesForLayout(layout: ILayoutDetails): LayoutRecordType[] {
    switch (layout.layoutViewType) {
        case "home":
            return [LayoutRecordType.GLOBAL];
        case "subcommunityHome":
        case "categoryList":
        case "discussionList":
            return [LayoutRecordType.GLOBAL, LayoutRecordType.SUBCOMMUNITY];
        case "nestedCategoryList":
        case "discussionCategoryPage":
        case "discussionThread":
        case "ideaThread":
        case "questionThread":
            return [LayoutRecordType.GLOBAL, LayoutRecordType.CATEGORY, LayoutRecordType.SUBCOMMUNITY];
        default:
            return [];
    }
}
