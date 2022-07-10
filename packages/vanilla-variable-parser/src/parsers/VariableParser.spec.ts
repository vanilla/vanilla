/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import path from "path";
import { VariableParser } from "./VariableParser";

const FIXTURE_ROOT = path.resolve(__dirname, "__fixtures__");

describe("VariableParser", () => {
    it("can parse a single file", () => {
        const varParser = new VariableParser();
        const result = varParser.parseFile(FIXTURE_ROOT, "basic.ts");

        expect(result).toMatchSnapshot();
    });

    it("can handle type expanders", () => {
        const parser = VariableParser.create().addTypeExpander({
            type: "font",
            expandType: (variable) => {
                return [
                    {
                        ...variable,
                        title: variable.title + " - " + "Color",
                        description: "Text color",
                        key: variable.key + ".color",
                        type: "string",
                    },
                    {
                        ...variable,
                        title: variable.title + " - " + "Size",
                        description: "Text size",
                        key: variable.key + ".size",
                        type: "number",
                    },
                ];
            },
        });

        const input = `
        /**
         * @varGroup thing.font
         * @title Thing Font
         * @expand font
         */
        `;

        const output = parser.parseString(input, "file.js");
        expect(output.errors).toHaveLength(0);
        expect(output.variables).toMatchSnapshot("font expansions");
    });
});
