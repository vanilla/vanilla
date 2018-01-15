/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

import * as apiUtility from "../api-utility";

describe("transformLegacyFormData", () => {
    it("lowercases the first letter of any other keys", () => {
        const input = {
            Foo: "tads",
            BBar: "test",
        };

        const output = {
            foo: "tads",
            bBar: "test",
        };

        expect(apiUtility.transformLegacyFormData(input)).toEqual(output);
    });

    it("transforms Announce", () => {
        const inputs = [
            { Announce: 0 },
            { Announce: 1 },
            { Announce: 2 },
        ];

        const outputs = [{
            pinned: false,
        }, {
            pinned: true,
            pinLocation: "recent",
        }, {
            pinned: true,
            pinLocation: "category",
        }];

        inputs.forEach((input, index) => {
            expect(apiUtility.transformLegacyFormData(input)).toEqual(outputs[index]);
        });
    });
});
