import * as utility from "../utility";

describe("resolvePromisesSequentially()", () => {
    it("resolves promises in order", () => {
        const order = [];

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

        expect.assertions(1);

        return utility.resolvePromisesSequentially(functions).then(() => {
            expect(order).toEqual(expectation);
        });
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

        expect.assertions(1);

        return expect(utility.resolvePromisesSequentially(functions)).resolves.toEqual(expectation);
    });

    it("passes the value of one promise to the next", () => {
        const func = (prev) => Number.isInteger(prev) ? Promise.resolve(prev + 1) : Promise.resolve(0);
        const functions = [func, func, func];
        const expectation = [0, 1, 2];


        return expect(utility.resolvePromisesSequentially(functions)).resolves.toEqual(expectation);
    });
});
