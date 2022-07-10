/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { hashString, splitStringLoosely, matchAtMention, labelize } from "./stringUtils";

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
