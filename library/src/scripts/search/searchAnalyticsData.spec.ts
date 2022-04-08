/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchForm, ISearchResult, ISearchResults } from "@library/search/searchTypes";
import { getSearchAnalyticsData, splitSearchTerms } from "./searchAnalyticsData";
import * as _appUtils from "@library/utility/appUtils";

interface ITermsCase {
    name: string;
    input: string;
    expectedTerms: string[];
    expectedNegativeTerms: string[];
}

const SPECIAL_CHARACTERS = ["!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "-", "+", "[", "]", "\\", "?", "."];

const testCases: ITermsCase[] = [
    {
        name: "Empty query",
        input: "",
        expectedTerms: [],
        expectedNegativeTerms: [],
    },
    {
        name: "Query with lots of whitespace",
        input: "    My  spacy query    ",
        expectedTerms: ["My", "spacy", "query"],
        expectedNegativeTerms: [],
    },
    {
        name: "Negative and positive terms",
        input: '   -"negativeTerm" heyyo  +"exact match"  howdy! "or or or"  ',
        expectedTerms: ["exact match", "or or or", "heyyo", "howdy"],
        expectedNegativeTerms: ["negativeTerm"],
    },
    {
        name: "Mutliple ors",
        input: `"quoted 1" "quoted 2" "quoted 3"`,
        expectedTerms: ["quoted 1", "quoted 2", "quoted 3"],
        expectedNegativeTerms: [],
    },
];

const createTestSearchForm = (params?: Partial<ISearchForm>): ISearchForm => {
    return {
        domain: "test-domain",
        query: "test-query",
        page: 1,
        sort: "relevance",
        initialized: true,
        ...params,
    };
};

const createMockResults = (params?: Partial<ISearchResults>): ISearchResults => {
    const makeResults = (numberOfResults: number = 10): ISearchResult[] => {
        return Array(numberOfResults)
            .fill(null)
            .map((_, id) => ({
                name: "test",
                url: "/",
                body: "test",
                excerpt: "test",
                recordID: id,
                recordType: "test",
                type: "article",
                breadcrumbs: [],
                dateUpdated: "",
                dateInserted: "",
                insertUserID: 0,
                insertUser: {
                    userID: 1,
                    name: "Bob",
                    photoUrl: "",
                    dateLastActive: "2016-07-25 17:51:15",
                },
                updateUserID: 0,
            }));
    };

    return {
        results: {
            ...makeResults(),
            ...(params?.results ?? {}),
        },
        pagination: {
            next: 2,
            prev: 0,
            total: 14,
            currentPage: 1,
            ...(params?.pagination ?? {}),
        },
    };
};

const mockSiteSection: _appUtils.ISiteSection = {
    basePath: "string",
    contentLocale: "en",
    sectionGroup: "",
    sectionID: "0",
    name: "Test",
    apps: {
        forum: true,
    },
    attributes: {
        categoryID: -1,
    },
};

jest.spyOn(_appUtils, "getSiteSection").mockReturnValue(mockSiteSection);

describe("splitSearchTerms", () => {
    testCases.forEach((testCase) => {
        test(testCase.name, () => {
            const actual = splitSearchTerms(testCase.input);
            expect(actual.terms).toStrictEqual(testCase.expectedTerms);
            expect(actual.negativeTerms).toStrictEqual(testCase.expectedNegativeTerms);
        });
    });

    describe("Special character handling", () => {
        SPECIAL_CHARACTERS.forEach((char) => {
            test(`character: '${char}'`, () => {
                const input = `${char} -"${char}"`;
                const actual = splitSearchTerms(input);

                // Only the negative term in quotes should have come through.
                expect(actual.terms).toStrictEqual([]);
                expect(actual.negativeTerms).toStrictEqual([char]);
            });
        });
    });
});

describe("getSearchAnalyticsData", () => {
    let form;
    let results;
    let actual;

    beforeEach(() => {
        form = createTestSearchForm();
        results = createMockResults();
        actual = getSearchAnalyticsData(form, results);
    });
    it("The type field is correct", () => {
        expect(actual.type).toBe("search");
    });
    it("The domain field is derived from the search form", () => {
        expect(actual.domain).toBe(form.domain);
    });
    it("The searchResults count field is derived from the total results", () => {
        expect(actual.searchResults).toEqual(results.pagination.total);
    });
    it("The page field is reflects the current page", () => {
        expect(actual.searchResults).toEqual(results.pagination.total);
    });
    it("The site section field is populated", () => {
        expect(actual.siteSection).toEqual(mockSiteSection);
    });
});
