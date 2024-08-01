/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { RouteComponentProps } from "react-router";
import { ILayoutQuery } from "@library/features/Layout/LayoutRenderer.types";
import { matchPath } from "react-router";
import { getRelativeUrl, siteUrl } from "@library/utility/appUtils";

export type IPathLayoutQueryMapper<T extends object> = (params: RouteComponentProps<T>) => ILayoutQuery<T>;
let layoutPaths: string[] = [];

export function addLayoutPaths(paths: string[]) {
    layoutPaths = [...layoutPaths, ...paths];
}

export function isLayoutRoute(url: string): boolean {
    if (!url.startsWith(siteUrl(""))) {
        return false;
    }
    const relative = getRelativeUrl(url);
    const result = !!matchPath(relative, {
        path: layoutPaths,
        exact: true,
    });
    return result;
}
