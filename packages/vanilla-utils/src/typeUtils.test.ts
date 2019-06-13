/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import { isInstanceOfOneOf } from "./typeUtils";

it("isInstanceOfOneOf", () => {
    /* tslint:disable:max-classes-per-file */
    class Thing1 {}
    class Thing2 {}
    class Thing3 {}
    class Thing4 {}

    const classes = [Thing1, Thing2, Thing3, Thing4];

    const thing2 = new Thing4();

    expect(isInstanceOfOneOf(thing2, classes)).eq(true);
    expect(isInstanceOfOneOf(5, classes)).not.eq(true);
});
