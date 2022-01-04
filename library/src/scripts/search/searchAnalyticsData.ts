/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { getSiteSection } from "@library/utility/appUtils";

interface IResultAnalyticsData {
    type: "search";
    domain: string;
    searchResults: number;
    searchQuery: ISplitSearchTerms;
    page: number;
    title: string;
    author: { authorID: number[]; authorName: string[] };
    recordType: string[];
    tag: { tagID: number[]; tagName: string[] };
    category: { categoryID: number[]; categoryName: string[] };
    kb: { kbID: number | null; kbName: string };
    siteSection: object;
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
export const getSearchAnalyticsData = (form, results): IResultAnalyticsData => {
    const resultsWithAnalyticsData: IResultAnalyticsData = {
        type: "search",
        domain: form.domain,
        searchResults: results.data.length,
        searchQuery: splitSearchTerms(form.query),
        page: results.pagination.currentPage,
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
    };

    if (form.authors && form.authors.length) {
        resultsWithAnalyticsData.author.authorID = form.authors.map((author) => author.value);
        resultsWithAnalyticsData.author.authorName = form.authors.map((author) => author.label);
    }
    if (form.tagsOptions && form.tagsOptions.length) {
        resultsWithAnalyticsData.tag.tagID = form.tagsOptions.map((tag) => tag.value);
        resultsWithAnalyticsData.tag.tagName = form.tagsOptions.map((tag) => tag.label);
    }
    if (form.category && form.category.length) {
        resultsWithAnalyticsData.category.categoryID = form.categoryOptions.map((category) => category.value);
        resultsWithAnalyticsData.tag.tagName = form.categoryOptions.map((category) => category.label);
    }
    if (form.knowledgeBaseOption) {
        resultsWithAnalyticsData.kb.kbID = form.knowledgeBaseOption.value;
        resultsWithAnalyticsData.kb.kbName = form.knowledgeBaseOption.label;
    }

    return resultsWithAnalyticsData;
};
