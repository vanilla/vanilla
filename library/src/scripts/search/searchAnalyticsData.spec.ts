/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

import { splitSearchTerms } from "./searchAnalyticsData";

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
