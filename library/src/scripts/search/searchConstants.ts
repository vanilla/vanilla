/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";

export const ALL_CONTENT_DOMAIN_NAME = "all_content";
export const ALLOWED_GLOBAL_SEARCH_FIELDS = ["query", "name", "insertUserIDs", "dateInserted", "page", "types"];
export const NEW_SEARCH_PAGE_ENABLED = getMeta("themeFeatures.useNewSearchPage", false);
export const MEMBERS_DOMAIN_NAME = "members";
export const MEMBERS_RECORD_TYPE = "user";
export const PLACES_DOMAIN_NAME = "places";
export const PLACES_KNOWLEDGE_BASE_TYPE = "knowledgeBase";
export const PLACES_GROUP_TYPE = "group";
export const PLACES_CATEGORY_TYPE = "category";
