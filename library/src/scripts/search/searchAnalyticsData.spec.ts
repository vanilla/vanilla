/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { ISearchForm, ISearchResult, ISearchResponse } from "@library/search/searchTypes";
import { getSearchAnalyticsData, IResultAnalyticsData, splitSearchTerms } from "./searchAnalyticsData";
import * as _appUtils from "@library/utility/appUtils";
import { SearchFixture } from "@library/search/__fixtures__/Search.fixture";

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
        name: "Multiple ors",
        input: `"quoted 1" "quoted 2" "quoted 3"`,
        expectedTerms: ["quoted 1", "quoted 2", "quoted 3"],
        expectedNegativeTerms: [],
    },
];

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

_appUtils.setMeta("siteSection", mockSiteSection);

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
    let form: ISearchForm;
    let response: ISearchResponse;
    let actual: IResultAnalyticsData;

    beforeAll(() => {
        form = SearchFixture.createMockSearchForm();
        response = SearchFixture.createMockSearchResults();
        actual = getSearchAnalyticsData(form, response);
    });
    it("The type field is correct", () => {
        expect(actual.type).toBe("search");
    });
    it("The domain field is derived from the search form", () => {
        expect(actual.domain).toBe(form.domain);
    });
    it("The searchResults count field is derived from the total results", () => {
        expect(actual.searchResults).toEqual(response.pagination.total);
    });
    it("The page field is reflects the current page", () => {
        expect(actual.searchResults).toEqual(response.pagination.total);
    });
    it("The site section field is populated", () => {
        expect(actual.siteSection).toEqual(mockSiteSection);
    });
    it("The source object is not populated when source is not passed into it", () => {
        expect(actual.source).toBeUndefined();
    });
    it("The source object is populated", () => {
        const source = { key: "community", label: "Community" };
        actual = getSearchAnalyticsData(form, response, source);
        expect(actual).toHaveProperty("source");
        expect(actual.source?.key).toBe(source.key);
        expect(actual.source?.label).toBe(source.label);
    });
    it("The author field is populated", () => {
        const formWithAuthors = SearchFixture.createMockSearchForm({
            authors: [
                {
                    value: 1,
                    label: "Bobby",
                },
            ],
        });
        actual = getSearchAnalyticsData(formWithAuthors, response);
        expect(actual.author.authorID.length).toEqual(1);
        expect(actual.author.authorID).toEqual(expect.arrayContaining([1]));
        expect(actual.author.authorName).toEqual(expect.arrayContaining(["Bobby"]));
    });
    it("The tag field is populated", () => {
        const formWithTags = SearchFixture.createMockSearchForm({
            tagsOptions: [
                {
                    value: 1,
                    label: "Tag 1",
                },
            ],
        });
        actual = getSearchAnalyticsData(formWithTags, response);
        expect(actual.tag.tagID.length).toEqual(1);
        expect(actual.tag.tagID).toEqual(expect.arrayContaining([1]));
        expect(actual.tag.tagName).toEqual(expect.arrayContaining(["Tag 1"]));
    });
    it("The category field is populated", () => {
        const formWithCategories = SearchFixture.createMockSearchForm({
            categoryOptions: [
                {
                    value: 0,
                    label: "General",
                },
            ],
        });
        actual = getSearchAnalyticsData(formWithCategories, response);
        expect(actual.category.categoryID.length).toEqual(1);
        expect(actual.category.categoryID).toEqual(expect.arrayContaining([0]));
        expect(actual.category.categoryName).toEqual(expect.arrayContaining(["General"]));
    });
    it("The knowledge base field is populated", () => {
        const formWithKnowledgeBase = SearchFixture.createMockSearchForm({
            knowledgeBaseOption: {
                value: 1,
                label: "Guides",
            },
        });
        actual = getSearchAnalyticsData(formWithKnowledgeBase, response);
        expect(actual).toHaveProperty("kb");
        expect(actual.kb.kbID).toEqual(1);
        expect(actual.kb.kbName).toEqual("Guides");
    });
});
