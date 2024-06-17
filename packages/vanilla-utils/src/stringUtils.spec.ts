/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hashString, splitStringLoosely, matchAtMention, labelize, isNumeric, matchWithWildcard } from "./stringUtils";

describe("hashString()", () => {
    it("the same string always results in the same value", () => {
        const str =
            "a; lksdjfl;aska;lskd fjaskl;dfj al;skdjfalsjkdfa;lksdjfl;kasdjflksaf;kbfjal;skdfbjanv;slkdfjbals;dkjfslkadfj;alsdjf;oiawjef;oiawbejvf;ioawbevf;aoiwebfjaov;wifebvl";
        expect(hashString(str)).toEqual(hashString(str));
    });

    it("different strings hash to different values", () => {
        const str1 = "a;slkdfjl;askdjfkl;asdjfkl;asjdfl;";
        const str2 =
            "a;sldkfjal;skdfjl;kasjdfl;k;laksjdf;laksjdf;laksjdf;lkajsd;lkfjaskl;dfjals;kdfjnal;skdjbfl;kasbdjfv;laskjbdfal;skdjfalv;skdjfalskdbjnfav;bslkdfjnalv;ksdfjbalskdfbjalvsk.dfjbalsv;kdbfjalsv;kdfjbadklsfjals";

        expect(hashString(str1)).not.toEqual(hashString(str2));
    });
});

type ParamsResultTuple = [string, string, string[]];
describe("splitStringLoosely()", () => {
    const paramsAndResults: ParamsResultTuple[] = [
        ["Test", "te", ["", "Te", "st"]],
        ["Stéphane", "Stéph", ["", "Stéph", "ane"]],
        ["Stéphane", "Stëph", ["", "Stéph", "ane"]],
        ["Stéphane", "St", ["", "St", "éphane"]],
        ["TestTest", "Te", ["", "Te", "st", "Te", "st"]],
        ["Tæst", "T", ["", "T", "æs", "t", ""]],
        ["Tæst", "Tæ", ["", "Tæ", "st"]],
        ["Tææst", "Tææ", ["", "Tææ", "st"]],
    ];

    paramsAndResults.forEach(([fullString, subString, result], index) => {
        it(`Case ${index}`, () => {
            expect(splitStringLoosely(fullString, subString)).toEqual(result);
        });
    });
});

function testSubjectsAndMatches(subjectsAndMatches: object) {
    Object.entries(subjectsAndMatches).map(([subject, match]) => {
        it(subject, () => {
            const result = matchAtMention(subject, true);

            if (result === null) {
                expect(result).toEqual(match);
            } else {
                expect(result.match).toEqual(match);
            }
        });
    });
}

describe("matching @mentions", () => {
    describe("simple mentions", () => {
        const goodSubjects = {
            "@System": "System",
            "Sometext @System": "System",
            "asdfasdf @joe": "joe",
        };

        testSubjectsAndMatches(goodSubjects);
    });

    describe("special characters", () => {
        const goodSubjects = {
            [`@"Séche"`]: "Séche",
            [`Something @"Séche"`]: "Séche",
            [`@"Umuüûū"`]: "Umuüûū",
            [`@Séche`]: "Séche", // Unquoted accent character
            [`@Umuüûū"`]: 'Umuüûū"',
        };

        testSubjectsAndMatches(goodSubjects);
    });

    describe("names with spaces", () => {
        const goodSubjects = {
            [`@"Someon asdf `]: "Someon asdf ",
            [`@"someone with a closed space"`]: "someone with a closed space",
            [`@"What about multiple spaces?      `]: "What about multiple spaces?      ",
        };

        const badSubjects = {
            "@someone with non-wrapped spaces": null,
            "@Some ": null,
        };

        testSubjectsAndMatches(goodSubjects);
        testSubjectsAndMatches(badSubjects);
    });

    describe("Closing characters", () => {
        const goodSubjects = {
            [`@Other Mention at end after linebreak
                @System`]: "System",
            [`
    Newline with special char
                               @"Umuüûū"`]: "Umuüûū",
        };

        const badSubjects = {
            [`@"Close on quote" other thing`]: null,
        };

        testSubjectsAndMatches(goodSubjects);
        testSubjectsAndMatches(badSubjects);
    });
});

describe("labelize()", () => {
    const tests = [
        ["fooBar", "Foo Bar"],
        ["foo  bar", "Foo Bar"],
        ["fooID", "Foo ID"],
        ["fooURL", "Foo URL"],
        ["foo_bar", "Foo Bar"],
        ["foo-bar", "Foo Bar"],
        ["foo-bar-baz", "Foo Bar Baz"],
        ["foo bar   baz", "Foo Bar Baz"],
        [
            `foo bar
        baz`,
            "Foo Bar Baz",
        ],
    ];
    tests.forEach(([str, expected]) => {
        it("str", () => {
            expect(labelize(str)).toBe(expected);
        });
    });
});

describe("isNumeric()", () => {
    const numeric = [10100, 100.4131, "1000", "0", "1", "-141", "+3141", "-311.131", "0.0000"];
    const notNumeric = ["a123123", "12313a", "--00000", {}, [], undefined, null];

    numeric.forEach((val) => {
        it(`is numeric - '${val}'`, () => {
            expect(isNumeric(val)).toBe(true);
        });
    });
    notNumeric.forEach((val) => {
        it(`is not numeric - '${val}'`, () => {
            expect(isNumeric(val)).toBe(false);
        });
    });
});

describe("matchWithWildcard", () => {
    it("Matches partial string", () => {
        const string = "vanillaforums";
        const rules = "vanilla*";
        expect(matchWithWildcard(string, rules)).toBe(true);
    });
    it("Does not match unrelated strings", () => {
        const string = "higherlogic";
        const rules = "vanilla*";
        expect(matchWithWildcard(string, rules)).toBe(false);
    });
    it("Does not match rule without wildcard", () => {
        const string = "vanillaforums";
        const rules = "vanilla";
        expect(matchWithWildcard(string, rules)).toBe(false);
    });
    it("Match with preceding wildcard", () => {
        const string = "vanillaforums";
        const rules = "*forums";
        expect(matchWithWildcard(string, rules)).toBe(true);
    });
    it("Match with ruleset delimited with new lines", () => {
        const string = "vanillaforums";
        const rules = "something\nelse\n*forums";
        expect(matchWithWildcard(string, rules)).toBe(true);
    });
    it("Undefined matcher returns null", () => {
        const string = "vanillaforums";
        expect(matchWithWildcard(string, undefined as unknown as string)).toBeNull();
    });
    it("Matches without wildcard", () => {
        const string = "vanillaforums";
        const rules = "vanillaforums\nhigherlogic";
        expect(matchWithWildcard(string, rules)).toBe(true);
    });
    it("Matches with wildcard and slashes", () => {
        const string = "vanillaforums.com";
        const rules = "vanillaforums.com/*";
        expect(matchWithWildcard(string, rules)).toBe(true);
    });
});
