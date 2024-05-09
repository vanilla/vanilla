/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import chai, { expect } from "chai";
import asPromised from "chai-as-promised";
import { concatNewRows } from "./apiUtils";
chai.use(asPromised);

describe("concatNewRows()", () => {
    const testData = () => [
        { id: 1, n: "foo" },
        { id: 3, n: "bar" },
    ];
    const pluck = (o) => o.id;

    it("concatenates new data", () => {
        let t = testData();
        concatNewRows(t, [{ id: 2, n: "baz" }], pluck);
        expect(t).length(3);
    });

    it("leaves old data alone", () => {
        let t = testData();
        let r1 = t[1];
        concatNewRows(
            t,
            [
                { id: 3, n: "bar" },
                { id: 4, n: "baz" },
            ],
            pluck,
        );
        expect(t).length(3);
        expect(t[1]).equals(r1);
    });
});
