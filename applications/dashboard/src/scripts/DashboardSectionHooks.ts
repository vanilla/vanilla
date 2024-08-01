import * as DashboardSectionActions from "@dashboard/DashboardSectionActions";
import {
    useDashboardSectionDispatch,
    useDashboardSectionSelector as useSelector,
} from "@dashboard/DashboardSectionSlice";
import { IDashboardSection, IDashboardSectionStore } from "@dashboard/DashboardSectionType";
import { Loadable, LoadStatus } from "@library/@types/api/core";
import { useEffect, useMemo } from "react";
import { bindActionCreators } from "redux";

export function useDashboardSectionActions() {
    const dispatch = useDashboardSectionDispatch();
    return useMemo(() => bindActionCreators(DashboardSectionActions, dispatch), [dispatch]);
}

export function useDashboardSection(): Loadable<IDashboardSection[]> {
    const dashboardSections = useSelector((state: IDashboardSectionStore) => state.dashboard?.dashboardSections);
    const { fetchDashboardSections } = useDashboardSectionActions();

    useEffect(() => {
        if (dashboardSections.status === LoadStatus.PENDING) {
            fetchDashboardSections();
        }
    }, [fetchDashboardSections, dashboardSections.status]);

    return dashboardSections;
}
