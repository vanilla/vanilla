/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import type { INavigationItemBadge } from "@library/@types/api/core";
import apiv2 from "@library/apiv2";
import { useQuery, useQueryClient } from "@tanstack/react-query";

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
        children: GroupLink[];
    }

    export interface Section {
        name: string;
        id: string;
        description: string;
        url: string;
        children: Group[];
    }
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
