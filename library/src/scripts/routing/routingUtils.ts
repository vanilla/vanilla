/**
 * @author Mihran Abrahamian <mihran.abrahamian@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

import { siteUrl } from "@library/utility/appUtils";
import { spaceshipCompare } from "@vanilla/utils";

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
