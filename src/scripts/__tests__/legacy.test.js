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

// Regex tests for at.who. See global.js `matcher()` at line 1902
var regexStr =
    '(?:^|\\s)' + // Space before
    '@' + // @ Symbol triggers the match
    '(?:(\\w+)' + // Any ASCII based letter characters
    '|' + // Or
    '"([^"\\u0000-\\u001f\\u007f-\\u009f\\u2028]+?)"?)' + // Almost any character if quoted. With or without the last quote.
    '(?:\\n|$)'; // Newline terminates.

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
        ];

        const badSubjects = [
            // "@a", // 2 letters required for matching.
        ];

        subjects.forEach(testMatchingSubject);
        badSubjects.forEach(testFailingSubject);
    });

    describe("special characters", () => {
        const subjects = [
            `@"Séche"`,
            `Something @"Séche"`,
            `@"Umuüûū"`,
        ];

        const badSubjects = [
            `@Séche`, // Unquoted accent character
        ];

        subjects.forEach(testMatchingSubject);
        badSubjects.forEach(testFailingSubject);
    });

    describe("names with spaces", () => {
        const subjects = [
            `@"Someon asdf `,
            `@"someone with a closed space"`,
            `@"What about multiple spaces?      `,
        ];

        const badSubjects = [
            `@someone with non-wrapped spaces`,
            `@Some `,
        ];

        subjects.forEach(testMatchingSubject);
        badSubjects.forEach(testFailingSubject);
    });

    describe("Closing characters", () => {
        const subjects = [
            `@Other Mention at end after linebreak   
                @System`,
            `Newline with special char
                           @"Umuüûū"`,
        ];

        const badSubjects = [
            `@"Close on quote" other thing`,
        ];

        subjects.forEach(testMatchingSubject);
        badSubjects.forEach(testFailingSubject);
    });
});
