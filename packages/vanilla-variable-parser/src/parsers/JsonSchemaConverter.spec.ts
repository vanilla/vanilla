import { JSONSchema4 } from "json-schema";
import { JsonSchemaConverter } from "./JsonSchemaConverter";
import { IVariable } from "./VariableParser";
import * as JsonSchema from "json-schema";

describe("JsonSchemaConverter", () => {
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
        ];

        const expected: JSONSchema4 = {
            type: "object",
            $schema: "http://json-schema.org/schema",
            properties: {
                "myVar.thing": {
                    title: "MyVar - Thing",
                    description: "Description!!!",
                    markdownDescription: "Description!!!",
                    "x-sinceVersion": "2020.024",
                    type: ["string", "null"],
                },
                "myVar.thing2": {
                    title: "MyVar - Thing 2",
                    description: "Description2!!!",
                    markdownDescription: "Description2!!!",
                    type: "number",
                },
            },
        };

        const actual = JsonSchemaConverter.convertVariables(variables);
        expect(actual).toEqual(expected);

        // Test validation with it.
        const result = JsonSchema.validate(
            {
                "myVar.thing": null,
                "myVar.thing2": 42,
            },
            actual,
        );

        expect(result.valid).toBeTruthy();
    });
});
