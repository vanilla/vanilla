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
const regex = /(?:^|\s)(@(?=(")?)(\w+|\1(?:[^\u0000-\u001f\u007f-\u009f\u2028]+?)\1))(?:\s|$)/gi;

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
            `@Séche`,
            `Something @Séche`,
            `@Umuüûū`,
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
            `@"What about multiple spaces?      `,
        ];

        subjects.forEach(testMatchingSubject);
        badSubjects.forEach(testFailingSubject);
    });

    describe.only("Closing characters", () => {
        const subjects = [
            `@Other Mention at end after linebreak   
                @System`,
            `Newline with special char
                           @Umuüûū`,
        ];

        const badSubjects = [
            `@close on newline
                other text`,
            `@"Close on quote" other thing`,
            `@"Do we close on a newline with quotes?
                other text`,
            `@Other Mention on other line
                @"Someone with spaces"  
                @System more text`,
        ];

        subjects.forEach(testMatchingSubject);
        badSubjects.forEach(testFailingSubject);
    });
});
