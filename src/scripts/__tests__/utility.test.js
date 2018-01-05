/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

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

// describe("logging", () => {
//     // // @ts-ignore
//     // global.console = {
//     //     error: jest.fn();
//     // }
// })

describe("hashString", () => {
    test("the same string always results in the same value", () => {
        const str = "a; lksdjfl;aska;lskd fjaskl;dfj al;skdjfalsjkdfa;lksdjfl;kasdjflksaf;kbfjal;skdfbjanv;slkdfjbals;dkjfslkadfj;alsdjf;oiawjef;oiawbejvf;ioawbevf;aoiwebfjaov;wifebvl";
        expect(utility.hashString(str)).toBe(utility.hashString(str));
    });

    test("different strings hash to different values", () => {
        const str1 = "a;slkdfjl;askdjfkl;asdjfkl;asjdfl;";
        const str2 = "a;sldkfjal;skdfjl;kasjdfl;k;laksjdf;laksjdf;laksjdf;lkajsd;lkfjaskl;dfjals;kdfjnal;skdjbfl;kasbdjfv;laskjbdfal;skdjfalv;skdjfalskdbjnfav;bslkdfjnalv;ksdfjbalskdfbjalvsk.dfjbalsv;kdbfjalsv;kdfjbadklsfjals";

        expect(utility.hashString(str1)).not.toBe(utility.hashString(str2));
    });
});

describe("generateRandomString", () => {
    it("generates strings of a proper length", () => {
        const possibleValues = [
            3,
            41000,
            0,
            424,
            23,
            255
        ];

        possibleValues.forEach(value => {
            expect(utility.generateRandomString(value).length).toBe(value);
        })
    })

    it("errors when passed an negative number", () => {
        expect(() => utility.generateRandomString(-1)).toThrowError();
    });

    it("errors when passed a non-integer number", () => {
        // @ts-ignore
        expect(() => utility.generateRandomString("asd")).toThrowError();
        expect(() => utility.generateRandomString(123.42)).toThrowError();
    })
});

describe("metaDataFunctions", () => {
    beforeEach(() => {
        // @ts-ignore
        if (!global.gdn || !global.gdn.meta) {
            global.gdn = {};
            global.gdn.meta = {};
        }

        global.gdn.meta = {
            foo: "foo"
        }
    })

    it("can fetch existing metaData", () => {
        expect(utility.getMeta("foo")).toBe("foo");
    })

    it("return a default value if the requested one can't be found", () => {
        expect(utility.getMeta("baz", "fallback")).toBe("fallback");
    })

    it("can set a new meta value", () => {
        utility.setMeta("test", "result");
        expect(utility.getMeta("test")).toBe("result");
    })

    it("can override existing values with new ones", () => {
        utility.setMeta("foo", "foo2");
        expect(utility.getMeta("foo")).toBe("foo2");
    })
})
