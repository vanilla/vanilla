/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { Loadable, LoadStatus } from "@library/@types/api/core";
import { ICoreStoreState } from "@library/redux/reducerRegistry";

export interface IDashboardSectionStore extends ICoreStoreState {
    dashboard: IDashboardSectionState;
}

export interface IDashboardLinkBadge {
    type: string;
    url?: string;
    text?: string;
}

export interface IDashboardGroupLink {
    name: string;
    id: string;
    parentID: string;
    url: string;
    react: boolean;
    badge?: IDashboardLinkBadge;
}

export interface IDashboardSectionGroup {
    name: string;
    id: string;
    children: IDashboardGroupLink[];
}

export interface IDashboardSection {
    name: string;
    id: string;
    description: string;
    url: string;
    children: IDashboardSectionGroup[];
}

export interface IDashboardSectionState {
    dashboardSections: Loadable<IDashboardSection[]>;
}

export const IDashboardSectionInitialState: IDashboardSectionState = {
    dashboardSections: { status: LoadStatus.PENDING },
};
