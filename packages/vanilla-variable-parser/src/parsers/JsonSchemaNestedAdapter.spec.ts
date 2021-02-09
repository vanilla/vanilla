/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { JSONSchema4 } from "json-schema";
import { JsonSchemaNestedAdapter } from "./JsonSchemaNestedAdapter";
import { IVariable, IVariableGroup } from "./VariableParser";
import * as JsonSchema from "json-schema";

describe("JsonSchemaNestedAdapter", () => {
    it("can convert variables to a JSON schema", () => {
        const variables: IVariable[] = [
            {
                key: "myVar.thing",
                title: "MyVar - Thing",
                description: "Description!!!",
                sinceVersion: "2020.024",
                type: ["string", "null"],
            },
            {
                key: "myVar.thing2",
                title: "MyVar - Thing 2",
                description: "Description2!!!",
                type: "number",
            },
            {
                key: "syntheticGroup.otherThing",
                title: "Other Thing",
                type: "string",
            },
        ];

        const groups: IVariableGroup[] = [
            {
                key: "myVar",
                title: "My Var",
            },
        ];

        const expected: JSONSchema4 = {
            type: "object",
            $schema: "http://json-schema.org/schema",
            properties: {
                myVar: {
                    type: "object",
                    title: "My Var",
                    "x-key": "myVar",
                    properties: {
                        thing: {
                            title: "MyVar - Thing",
                            description: "Description!!!",
                            markdownDescription: "Description!!!",
                            "x-sinceVersion": "2020.024",
                            type: ["string", "null"],
                            "x-key": "myVar.thing",
                        },
                        thing2: {
                            title: "MyVar - Thing 2",
                            description: "Description2!!!",
                            markdownDescription: "Description2!!!",
                            type: "number",
                            "x-key": "myVar.thing2",
                        },
                    },
                },
                syntheticGroup: {
                    type: "object",
                    title: "Synthetic Group",
                    "x-key": "syntheticGroup",
                    properties: {
                        otherThing: {
                            title: "Other Thing",
                            type: "string",
                            "x-key": "syntheticGroup.otherThing",
                        },
                    },
                },
            },
        };

        const adapter = new JsonSchemaNestedAdapter(variables, groups);
        const actual = adapter.asJsonSchema();
        expect(actual).toEqual(expected);

        // Test validation with it.
        const result = JsonSchema.validate(
            {
                myVar: {
                    thing: null,
                    thing2: 42,
                },
            },
            actual,
        );

        expect(result.valid).toBeTruthy();
    });
});
