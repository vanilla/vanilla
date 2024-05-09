/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { getMeta } from "@library/utility/appUtils";
import { SearchQueryOperator } from "@library/search/searchTypes";

export const EMPTY_SEARCH_DOMAIN_KEY = "empty";
export const ALL_CONTENT_DOMAIN_KEY = "all_content";
export const ALLOWED_GLOBAL_SEARCH_FIELDS = ["domain", "scope", "page", "query", "sort", "queryOperator"];
export const NEW_SEARCH_PAGE_ENABLED = getMeta("themeFeatures.useNewSearchPage", false);
export const MEMBERS_RECORD_TYPE = "user";

export const PLACES_KNOWLEDGE_BASE_TYPE = "knowledgeBase";
export const PLACES_GROUP_TYPE = "group";
export const PLACES_CATEGORY_TYPE = "category";

export const DEFAULT_SEARCH_QUERY_OPERATOR = SearchQueryOperator.AND;
