/**
 * Tests for code that in our legacy javascript that we haven't necessarily been able
 * move into modules yet.
 *
 * Everything here should be linked to the piece of code it connects to and vice-versa.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */
/*eslint-disable no-control-regex*/

// Regex tests for at.who
const regex = /(?:^|\s)@"?([\f\r\t\v​ \u1680​\u180eA-Za-z0-9_+-]*)"?(?:\n|$)|(?:^|\s)@"?([^\x00-\x09\x0B-\xff]*)"?(?:\n|$)/gi;

function testMatchingSubject(subject) {
    test(subject, () => {
        expect(subject).toEqual(expect.stringMatching(regex));
    });
}

function testFailingSubject(subject) {
    test(subject, () => {
        expect(subject).not.toEqual(expect.stringMatching(regex));
    });
}

describe("matching @mentions", () => {
    describe("simple mentions", () => {
        const subjects = [
            `@System`,
            `Sometext @System`,
            `Sometext with linebreak   
                @System`,
        ];

        const badSubjects = [
            "@a", // 2 letters required for matching.
        ];

        subjects.forEach(testMatchingSubject);

        badSubjects.forEach(testFailingSubject);
    });

    describe("special characters", () => {
        const subjects = [
            `@Séche`,
            `Something @Séche`,
            `@Umuüûū`,
            `Newline with special char
                           @Umuüûū,`,
        ];

        subjects.forEach(testMatchingSubject);

        // badSubjects.forEach(testFailingSubject);
    });

    describe("names with spaces", () => {
        const subjects = [
            `@"Someon asdf `,
            `@Some `, // Single space but no closing braces?
        ];

        const badSubjects = [
            `@someone with non-wrapped spaces`,
            `@"someone with a closed space"`,
            `@"Do we close on a newline?
                other text`,
            `@"What about multiple spaces?      `,
        ];

        subjects.forEach(testMatchingSubject);

        badSubjects.forEach(testFailingSubject);
    });

});
