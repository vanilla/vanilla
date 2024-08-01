/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { INavigationItem } from "@library/@types/api/core";
import { IActiveRecord } from "@library/navigation/SiteNavNode";
import { siteUrl } from "@library/utility/appUtils";
import { isNumeric, spaceshipCompare } from "@vanilla/utils";
import qs from "qs";
import { useLocation } from "react-router";

export function findMatchingPath(paths: string[], currentPath: string): string | null {
    const currentPathUrl = new URL(siteUrl(currentPath));

    const matchingPaths = paths.filter((path) => {
        return currentPathUrl.pathname.startsWith(path);
    });

    // Favor the longest matching path.
    const sorted = matchingPaths.sort((a, b) => {
        return -spaceshipCompare(a.length, b.length);
    });

    return sorted[0] ?? undefined;
}

export function flattenItems<T>(items: T[], key: string): T[] {
    return items.reduce<T[]>((flattenedItems, item) => {
        flattenedItems.push(item);
        if (Array.isArray(item[key])) {
            flattenedItems = flattenedItems.concat(flattenItems(item[key], key));
        }
        return flattenedItems;
    }, []);
}

export function useActiveNavRecord(
    navItems: INavigationItem[],
    active?: IActiveRecord | undefined | null,
): IActiveRecord | null {
    const location = useLocation();

    if (active) {
        return active;
    }

    const flatNavItems = flattenItems(navItems, "children");
    const urls = flatNavItems.filter((item) => !!item.url).map(({ url }) => url!);
    const matchingPath = findMatchingPath(urls, location.pathname);
    const matchingNavItem = flatNavItems.find(({ url }) => url === matchingPath);
    return matchingNavItem ?? null;
}

export function useQueryParam<T>(param: string): T | null;
export function useQueryParam<T>(param: string, defaultValue: T): T;
export function useQueryParam<T>(param: string, defaultValue?: T): T | null {
    const search = useLocation().search;
    const query = qs.parse(search, { ignoreQueryPrefix: true });
    let paramValue = query[param];

    if (paramValue == null) {
        return defaultValue ?? null;
    }

    let finalValue: T | null = null;
    if (typeof defaultValue === "boolean") {
        finalValue = (paramValue === "true" ? true : false) as any;
    } else if (isNumeric(paramValue)) {
        const parsed = parseInt(paramValue, 10);
        if (!Number.isNaN(parsed)) {
            finalValue = parsed as unknown as T;
        } else {
            finalValue = defaultValue ?? null;
        }
    } else {
        finalValue = paramValue as unknown as T;
    }
    return finalValue;
}

export function useQueryParamPage(): number {
    const page = useQueryParam("page", 1);
    return page;
}
