/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { useQuery, useQueryClient } from "@tanstack/react-query";

import type { INavigationItemBadge } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";

export namespace DashboardMenusApi {
    export interface GroupLink {
        name: string;
        id: string;
        parentID: string;
        url: string;
        react: boolean;
        badge?: INavigationItemBadge;
    }

    export interface Group {
        name: string;
        id: string;
        children: GroupLink[] | Group[];
    }

    export interface Section {
        name: string;
        id: string;
        description: string;
        url: string;
        children: Group[];
    }
}

// Type guards to help distinguish between GroupLink[] and Group[]
export function isGroupLink(
    item: DashboardMenusApi.GroupLink | DashboardMenusApi.Group,
): item is DashboardMenusApi.GroupLink {
    return "url" in item && "react" in item;
}

export function isGroup(item: DashboardMenusApi.GroupLink | DashboardMenusApi.Group): item is DashboardMenusApi.Group {
    return "children" in item && !("url" in item);
}

export function hasGroupLinkChildren(
    group: DashboardMenusApi.Group,
): group is DashboardMenusApi.Group & { children: DashboardMenusApi.GroupLink[] } {
    return group.children.length === 0 || isGroupLink(group.children[0]);
}

export function hasGroupChildren(
    group: DashboardMenusApi.Group,
): group is DashboardMenusApi.Group & { children: DashboardMenusApi.Group[] } {
    return group.children.length > 0 && isGroup(group.children[0]);
}

export class DashboardMenusApi {
    public static async getMenus() {
        const response = await apiv2.get<DashboardMenusApi.Section[]>("/dashboard/menus");
        return response.data;
    }
    public static useMenus() {
        return useQuery({
            queryKey: ["dashboardMenus"],
            queryFn: () => this.getMenus(),
        });
    }
    public static useUtils() {
        const queryClient = useQueryClient();
        return {
            invalidate: () => {
                return queryClient.invalidateQueries(["dashboardMenus"]);
            },
        };
    }
}
