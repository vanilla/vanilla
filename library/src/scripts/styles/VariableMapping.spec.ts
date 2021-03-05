/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { variableFactory } from "@library/styles/styleUtils";
import { GlobalVariableMapping, LocalVariableMapping } from "@library/styles/VariableMapping";
import { Variables } from "@library/styles/Variables";

describe("VariableMappings", () => {
    it("maps local values", () => {
        const initial = {
            fontSize: 24,
            padding: {
                top: 24,
            },
            spacing: 12,
        };

        const expected = {
            font: {
                size: 24,
            },
            spacing: {
                top: 24,
                all: 12,
            },
        };

        const mapping = new LocalVariableMapping({
            "font.size": "fontSize",
            spacing: "padding",
            "spacing.all": "spacing",
        });

        expect(mapping.map(initial, initial)).toEqual(expected);
    });

    it("maps global values", () => {
        const initial = {
            font: {
                size: 12,
            },
        };

        const globalVars = {
            otherThing: {
                font: {
                    weight: "bold",
                },
                items: [1, 2, 3],
            },
        };

        const expected = {
            font: {
                size: 12,
                weight: "bold",
            },
            items: [1, 2, 3],
        };

        const mapping = new GlobalVariableMapping({
            "font.weight": ["otherThing.font.weight"],
            items: "otherThing.items",
        });
        expect(mapping.map(initial, globalVars)).toEqual(expected);
    });

    it("works in a variable factory", () => {
        const themeVars = {
            otherThing: {
                spacing: {
                    all: 24,
                },
            },
            myThing: {
                title: {
                    fontSize: 24,
                },
            },
        };
        const makeVars = variableFactory("myThing", themeVars, [
            new LocalVariableMapping({
                "title.font.size": "title.fontSize",
            }),
            new GlobalVariableMapping({
                spacing: "otherThing.spacing",
            }),
        ]);

        const title = makeVars("title", {
            font: Variables.font({ size: 12 }),
        });

        const spacing = makeVars("spacing", Variables.spacing({}));

        expect(title.font.size).toBe(24);
        expect(spacing.all).toBe(24);
    });
});
