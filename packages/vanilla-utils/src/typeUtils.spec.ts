/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { forceBool, isInstanceOfOneOf } from "./typeUtils";

it("isInstanceOfOneOf", () => {
    class Thing1 {}
    class Thing2 {}
    class Thing3 {}
    class Thing4 {}

    const classes = [Thing1, Thing2, Thing3, Thing4];

    const thing2 = new Thing4();

    expect(isInstanceOfOneOf(thing2, classes)).toEqual(true);
    expect(isInstanceOfOneOf(5, classes)).not.toEqual(true);
});

describe("forceBool", () => {
    const cases = [
        ["true", true],
        ["false", false],
        [false, false],
        [true, true],
        [0, false],
        [1, true],
        [10000, true],
        [-1, true],
        ["other", true],
        ["", false],
        [[], true],
        [{}, true],
        [null, false],
        [undefined, false],
    ];

    cases.forEach(([input, expected]) => {
        it(`converts ${JSON.stringify(input)} to ${JSON.stringify(expected)}`, () => {
            expect(forceBool(input)).toBe(expected);
        });
    });
});
