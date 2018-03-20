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

/**
 * Put together the non-excluded characters.
 *
 * @param {boolean} excludeWhiteSpace - Whether or not to exclude whitespace characters.
 *
 * @returns {string} A Regex string.
 */
function nonExcludedCharacters(excludeWhiteSpace) {
    var excluded = '[^' +
        '"' + // Quote character
        '\\u0000-\\u001f\\u007f-\\u009f' + // Control characters
        '\\u2028';// Line terminator

    if (excludeWhiteSpace) {
        excluded += '\\s';
    }

    excluded += "]";
    return excluded;
}

var regexStr =
    '(?:^|\\s)' + // Space before
    '@' + // @ Symbol triggers the match
    '(' +
    // One or more non-greedy characters that aren't excluded. White is allowed, but a starting quote is required.
    '"(' + nonExcludedCharacters(false) + '+?)"?' +

    '|' + // Or
    // One or more non-greedy characters that aren't exluded. Whitespace is excluded.
    '(' + nonExcludedCharacters(true) + '+?)"?' +

    ')' +
    '(?:\\n|$)'; // Newline terminates.
const regex = new RegExp(regexStr, 'gi');

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
    console.log(regex);
    describe("simple mentions", () => {
        const subjects = [
            `@System`,
            `Sometext @System`,
            `asdfasdf @joe`,
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
            `@Séche`, // Unquoted accent character
            `@Umuüûū"`,
        ];

        subjects.forEach(testMatchingSubject);
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
