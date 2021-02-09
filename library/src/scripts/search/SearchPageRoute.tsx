/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import RouteHandler from "@library/routing/RouteHandler";
import { NEW_SEARCH_PAGE_ENABLED } from "@library/search/searchConstants";
import Loader from "@library/loaders/Loader";

export function makeSearchUrl(): string {
    return NEW_SEARCH_PAGE_ENABLED ? "/search" : "/kb/search";
}

export const SearchPageRoute = new RouteHandler(
    () => import(/* webpackChunkName: "pages/search" */ "@vanilla/library/src/scripts/search/SearchPage"),
    makeSearchUrl(),
    makeSearchUrl,
    Loader,
);
