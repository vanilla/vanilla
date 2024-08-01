import { flattenNests } from "@library/styles/styleShim";

describe("flattenNest", () => {
    it("flattens $nest declarations", () => {
        const initial = {
            fontSize: 24,
            $nest: {
                ".thing": {
                    fontSize: 32,
                    $nest: {
                        ".otherThing": {
                            fontSize: 40,
                        },
                    },
                },
            },
        };

        const expected = {
            fontSize: 24,
            ".thing": {
                fontSize: 32,
                ".otherThing": {
                    fontSize: 40,
                },
            },
        };

        expect(expected).toEqual(flattenNests(initial));
    });
});
