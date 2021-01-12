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

    it("applies varGroup prefixes and common descriptions", () => {
        const input = `
        /**
         * @varGroup thing
         * @commonTitle PREFIX
         * @commonDescription DESCRIPTION
         */

         /**
          * @var thing.var1
          * @title Variable 1
          * @description My Description
          */

         /**
          * @var thing.var2
          * @title Variable 2
          * @description My Description 2
          */
        `;
        const parser = VariableParser.create();
        const output = parser.parseString(input, "file.js");

        expect(output.errors).toHaveLength(0);
        expect(output.variables).toMatchSnapshot("varGroupPrefixes expansions");
    });

    it("handles nested parentGroups", () => {
        const input = `
        /**
         * @varGroup thing
         * @commonTitle PREFIX
         * @commonDescription DESCRIPTION
         */

         /**
          * @var thing.nested.var1
          * @title Variable 1
          * @description My Description
          */
        `;
        const parser = VariableParser.create();
        const output = parser.parseString(input, "file.js");

        expect(output.errors).toHaveLength(0);
        expect(output.variables[0].title).toEqual("PREFIX - Variable 1");
    });
});
