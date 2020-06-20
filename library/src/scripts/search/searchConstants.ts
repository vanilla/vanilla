/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";

export const ALL_CONTENT_DOMAIN_NAME = "all_content";
export const ALLOWED_GLOBAL_SEARCH_FIELDS = ["query", "name", "insertUserIDs", "dateInserted", "page"];
export const NEW_SEARCH_PAGE_ENABLED = getMeta("themeFeatures.useNewSearchPage", false);
