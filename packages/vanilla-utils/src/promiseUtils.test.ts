/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { expect } from "chai";
import asPromised from "chai-as-promised";
import { resolvePromisesSequentially } from "./promiseUtils";
chai.use(asPromised);

describe("resolvePromisesSequentially()", () => {
    it("resolves promises in order", async () => {
        const order: number[] = [];

        const func1 = () => {
            order.push(1);
        };
        const func2 = () => {
            return new Promise(resolve => {
                setTimeout(() => {
                    order.push(2);
                    resolve();
                }, 50);
            });
        };
        const func3 = () => {
            order.push(3);
        };

        const functions = [func1, func2, func3];
        const expectation = [1, 2, 3];

        await resolvePromisesSequentially(functions);
        expect(order).to.deep.equal(expectation);
    });

    it("returns all of the results in order", () => {
        const func1 = () => Promise.resolve(1);
        const func2 = () => {
            return new Promise(resolve => {
                setTimeout(() => {
                    resolve(2);
                }, 50);
            });
        };
        const func3 = () => Promise.resolve(3);

        const functions = [func1, func2, func3];
        const expectation = [1, 2, 3];

        return expect(resolvePromisesSequentially(functions)).to.eventually.deep.equal(expectation);
    });

    it("passes the value of one promise to the next", () => {
        const func = prev => (Number.isInteger(prev) ? Promise.resolve(prev + 1) : Promise.resolve(0));
        const functions = [func, func, func];
        const expectation = [0, 1, 2];

        return expect(resolvePromisesSequentially(functions)).to.eventually.deep.equal(expectation);
    });
});
