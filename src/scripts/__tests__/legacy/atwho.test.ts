/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import { matchAtMention } from "@core/legacy/atwho";

function testSubjectsAndMatches(subjectsAndMatches: object) {
    Object.entries(subjectsAndMatches).map(([subject, match]) => {
        test(subject, () => {
            expect(matchAtMention("", subject, true)).toBe(match);
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
            [`@Umuüûū"`]: "Umuüûū\"",
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
