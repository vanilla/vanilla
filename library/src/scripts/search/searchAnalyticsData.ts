/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

import { IComboBoxOption } from "@library/features/search/SearchBar";
import { ISearchForm, ISearchResponse, ISearchSource } from "@library/search/searchTypes";
import { getSiteSection } from "@library/utility/appUtils";
import { ICommunitySearchTypes } from "@vanilla/addon-vanilla/search/communitySearchTypes";
import { RecordID } from "@vanilla/utils";

interface ITrackedSearchSource {
    key: ISearchSource["key"];
    label: string;
}
export interface IResultAnalyticsData {
    type: "search";
    domain: string;
    searchResults: number;
    searchQuery: ISplitSearchTerms;
    page: number;
    title: string;
    author: { authorID: RecordID[]; authorName: string[] };
    recordType: string[];
    tag: { tagID: RecordID[]; tagName: string[] };
    category: { categoryID: RecordID[]; categoryName: string[] };
    kb: { kbID: RecordID | null; kbName: string };
    siteSection: object;
    siteSectionID: string;
    source?: ITrackedSearchSource;
}

interface IPieces {
    and: string[];
    not: string[];
    or: string[];
    residue: string[];
}

interface ISplitSearchTerms {
    terms: string[];
    negativeTerms: string[];
    originalQuery: string;
}

/**
 * Returns search splitted/grouped query terms
 */
export const splitSearchTerms = (query: string): ISplitSearchTerms => {
    const queryData: ISplitSearchTerms = {
        terms: [],
        negativeTerms: [],
        originalQuery: query,
    };

    // no values searched empty search input
    if (queryData.originalQuery === "") {
        return queryData;
    }

    //we store and/not/or patterns separately just in case,
    //the actual output will just be grouped by 'negativeTerms' and "terms"
    const patterns = {
        and: /\+"((?:[^"]|\\.)*)"/g,
        not: /-"((?:[^"]|\\.)*)"/g,
        or: /"((?:[^"]|\\.)*)"/g,
    };

    const pieces: IPieces = {
        and: [],
        not: [],
        or: [],
        residue: [],
    };
    Object.keys(patterns).forEach((key) => {
        let quoted = query.match(patterns[key]);
        if (quoted && quoted.length) {
            pieces[key] = [];
            quoted.forEach((q) => {
                //remove quoted strings from query as we store them separately
                query = query.replace(q, "");

                q = q.replace(/"$/g, ""); // remove closing quote
                q = q.replace(/^[+-]?"/g, ""); //get rid of +/- and the opening quote

                if (key !== "or") {
                    pieces[key].push(q);

                    //avoid duplications in 'or'
                } else if (!pieces.and.includes(q) && !pieces.not.includes(q)) {
                    pieces[key].push(q);
                }
            });
        }
    });

    //remove special characters and empty spaces, store each term in array
    query = query.replace(/[!@#$%^\-&*()\\+[\]?.]/g, "").trim();
    pieces.residue = query ? query.split(" ").filter((term) => !!term) : [];

    queryData.negativeTerms = pieces.not ?? [];
    queryData.terms = [...pieces.and, ...pieces.or, ...pieces.residue];

    return queryData;
};

/**
 * Get structured data for search analytics
 */
export const getSearchAnalyticsData = (
    form: ISearchForm<
        ICommunitySearchTypes & {
            knowledgeBaseOption?: IComboBoxOption; //fixme: Knowledge should add this dynamically
        }
    >,
    response: ISearchResponse,
    searchSource?: ITrackedSearchSource,
): IResultAnalyticsData => {
    const resultsWithAnalyticsData: IResultAnalyticsData = {
        type: "search",
        domain: form.domain,
        searchResults: response.pagination.total ?? -1,
        searchQuery: splitSearchTerms(`${form.query}`),
        page: response.pagination?.currentPage ?? -1,
        title: form.name ?? "",
        author: {
            authorID: [],
            authorName: [],
        },
        recordType: form.types ?? [],
        tag: {
            tagID: [],
            tagName: [],
        },
        category: {
            categoryID: [],
            categoryName: [],
        },
        //we don't allow multiple kb filter in search so no mapping here
        kb: { kbID: null, kbName: "" },
        siteSection: getSiteSection(),
        siteSectionID: getSiteSection().sectionID,
    };

    if (form.authors && form.authors.length) {
        resultsWithAnalyticsData.author.authorID = form.authors.map((author) => author.value);
        resultsWithAnalyticsData.author.authorName = form.authors.map((author) => author.label);
    }
    if (form.tagsOptions && form.tagsOptions.length) {
        resultsWithAnalyticsData.tag.tagID = form.tagsOptions.map((tag) => tag.value);
        resultsWithAnalyticsData.tag.tagName = form.tagsOptions.map((tag) => tag.label);
    }
    if (form.categoryOptions && form.categoryOptions.length) {
        resultsWithAnalyticsData.category.categoryID = form.categoryOptions.map((category) => category.value);
        resultsWithAnalyticsData.category.categoryName = form.categoryOptions.map((category) => category.label);
    }
    if (form.knowledgeBaseOption) {
        resultsWithAnalyticsData.kb.kbID = form.knowledgeBaseOption.value;
        resultsWithAnalyticsData.kb.kbName = form.knowledgeBaseOption.label;
    }
    if (searchSource) {
        resultsWithAnalyticsData.source = searchSource;
    }

    return resultsWithAnalyticsData;
};
