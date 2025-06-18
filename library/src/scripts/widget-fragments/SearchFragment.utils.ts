/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { logDebug, notEmpty } from "@vanilla/utils";

/**
 * These are the API params allowed by the search endpoint
 */
const API_PARAMS = [
    "query",
    "recordTypes",
    "types",
    "discussionID",
    "categoryID",
    "followedCategories",
    "includeChildCategories",
    "includeArchivedCategories",
    "knowledgeBaseID",
    "knowledgeCategoryIDs",
    "name",
    "featured",
    "locale",
    "siteSiteSectionGroup",
    "insertUserNames",
    "insertUserIDs",
    "tags",
    "tagOperator",
    "queryOperator",
    "limit",
    "expandBody",
    "expand",
] as const;

export type SearchAPIParamType = (typeof API_PARAMS)[number];

/**
 * Define a mapping of field names to URL parameter names.
 * keys should match the the API params
 * values should be the equivalent URL params
 * if a value is null, it means that the key should not be included in the URL
 */
const PARAM_MAP: Record<SearchAPIParamType, string | null> = {
    // Filter the records using the supplied terms.
    query: "query",
    // Restrict the search to the specified main type(s) of records.
    recordTypes: "domain",
    // Restrict the search to the specified type(s) of records.
    types: "types",
    // Set the scope of the search to the comments of a discussion. Incompatible with recordType and type.
    discussionID: null,
    // Set the scope of the search to a specific category.
    categoryID: "categoryIDs",
    // Set the scope of the search to followed categories only.
    followedCategories: "followedCategories",
    // Search the specified category's subtree. Works with categoryID
    includeChildCategories: "includeChildCategories",
    // Allow search in archived categories.
    includeArchivedCategories: "includeArchivedCategories",
    // Filter the records by KnowledgeBase ID
    knowledgeBaseID: "knowledgeBaseOption",
    // Filter the records by KnowledgeCategory ID
    knowledgeCategoryIDs: null,
    // Filter the records by matching part of their name.
    name: "name",
    // Filter the records by their featured status.
    featured: null,
    // Filter the records by their locale.
    locale: null,
    // Filter the records by their site-section-group.
    siteSiteSectionGroup: null,
    // Filter the records by inserted user names.
    insertUserNames: "authors",
    // Filter the records by inserted userIDs.
    insertUserIDs: "insertUserIDs",
    // Filter discussions by matching tags.
    tags: "tags",
    // Tags search condition. Must be one of: "and", "or".
    tagOperator: "tagOperator",
    // Set the default search type.
    queryOperator: "queryOperator",
    // Desired number of items per page.
    limit: null,
    // Expand the results to include a rendered body field.
    expandBody: null,
    // Expand associated records using one or more valid field names. A value of "all" will expand all expandable fields.
    expand: null,
};

/**
 * This is a list of params which are exlcusive to specific record types.
 */
const EXCLUSIVE_RECORD_TYPES = {
    discussion: [
        "types",
        "categoryID",
        "followedCategories",
        "includeChildCategories",
        "includeArchivedCategories",
        "insertUserNames",
    ],
    article: ["knowledgeBaseID", "knowledgeCategoryIDs", "insertUserNames"],
};

const INCLUDE_INDEX = ["tags", "authors"];
const INCLUDE_OBJECT = ["authors", "tags", "knowledgeBaseID", "knowledgeCategoryIDs"];

/**
 * Convert the search API params to a URL encoded string
 * @param params - API params
 * @param optionData - Additional option data to populate the search filter multiselects
 * @returns string - URL encoded string
 */
export function convertSearchAPIParamsToURL(_params: Partial<Record<SearchAPIParamType, any>>): string {
    // We will be rewrinting the params object, so we need to create a copy of it.
    let params = { ..._params };

    // If there are recordTypes, we might need to remove params which are not allowed with it.
    if (params.hasOwnProperty("recordTypes")) {
        // A list of keys which need to removed from the params object.
        let exclusiveKeyList: string[] = [];

        // All the keys which are exclusive other record types
        exclusiveKeyList = Object.entries(EXCLUSIVE_RECORD_TYPES)
            .map(([recordType, exclusiveKeys]) => {
                if (params.recordTypes !== recordType) {
                    return exclusiveKeys;
                }
                return [];
            })
            .flat()
            .filter(notEmpty);

        // Some params can appear in the more than one record type, this will ensure it
        // does not appear in the exclusiveKeyList.
        if (EXCLUSIVE_RECORD_TYPES?.[params.recordTypes]) {
            exclusiveKeyList = [
                ...new Set([
                    ...exclusiveKeyList
                        .map((key) => {
                            if (!EXCLUSIVE_RECORD_TYPES?.[params.recordTypes].includes(key)) {
                                return key;
                            }
                        })
                        .flat()
                        .filter(notEmpty),
                ]),
            ];
        }

        // Remove keys which do not belong to the record type.
        exclusiveKeyList.forEach((key) => {
            logDebug(`${key} cannot be used with "${params.recordTypes}" recordType`);
            delete params[key];
        });
    }

    // Create a new URLSearchParams instance.
    const searchParams = new URLSearchParams();

    // Iterate over the parameters object.
    for (const [key, _value] of Object.entries(params)) {
        let value = _value;
        const urlParam = PARAM_MAP[key];

        // Skip parameters that don't have mapping.
        if (!urlParam) {
            continue;
        }

        // Helper function to expand an object to its label and value.
        const appendOptions = (item: Record<string, string>, index?: string | false) => {
            if (item.value) {
                searchParams.append(`${urlParam}${index ? `[${index}]` : ""}[value]`, item.value);
            }
            if (item.label) {
                searchParams.append(`${urlParam}${index ? `[${index}]` : ""}[label]`, item.label);
            }
        };

        // Arrays need special handling to include the index in the parameter name.
        if (Array.isArray(value)) {
            if (typeof value[0] === "object") {
                // Object values need to be split into label and value
                const includeIndex = INCLUDE_INDEX.includes(urlParam);
                value.forEach((item, index) => appendOptions(item, includeIndex && `${index}`));
            } else {
                // Arrays of strings
                value.forEach((item, index) => {
                    searchParams.append(
                        `${urlParam}[${index}]${INCLUDE_OBJECT.includes(urlParam) ? "[value]" : ""}`,
                        item,
                    );
                });
            }
        } else {
            if (typeof value === "object") {
                // Sometimes, even singluar values require to be indexed
                appendOptions(value, INCLUDE_INDEX.includes(urlParam) ? "0" : false);
            } else {
                searchParams.append(urlParam, value);
            }
        }
    }

    // Return a query string.
    return searchParams.toString();
}
