import { IVariable, VariableParser } from "./parsers/VariableParser";
import fse from "fs-extra";
import { JsonSchemaConverter } from "./parsers/JsonSchemaConverter";

export async function parseVariables(
    srcDirectory: string,
    pattern: string,
    outFile: string
) {
    console.log(
        `Extracting variable schema from files in ${srcDirectory} matching pattern ${pattern}`
    );

    const results: IVariable[] = [];
    const parser = VariableParser.create().addTypeExpander({
        type: "font",
        expandType: (variable: IVariable) => {
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
                    type: ["number", "string"],
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Weight",
                    description: "Text weight/boldness",
                    key: variable.key + ".weight",
                    type: "number",
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Line Height",
                    description: "Text line height",
                    key: variable.key + ".lineHeight",
                    type: ["number", "string"],
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Line Height",
                    description: "Text shadow",
                    key: variable.key + ".shadow",
                    type: "string",
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Alignment",
                    description: "Text alignment",
                    key: variable.key + ".align",
                    type: ["number", "string"],
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Family",
                    description: "Text alignment",
                    key: variable.key + ".family",
                    type: ["string", "array"],
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Transform",
                    description:
                        "Specifies how to capitalize an element's text. It can be used to make text appear in all-uppercase or all-lowercase, or with each word capitalized. ",
                    key: variable.key + ".transform",
                    type: ["string"],
                    enum: [
                        "capitalize",
                        "full-size-kana",
                        "full-width",
                        "lowercase",
                        "none",
                        "uppercase",
                    ],
                },
                {
                    ...variable,
                    title: variable.title + " - " + "Letter Spacing",
                    description:
                        "Sets the spacing behavior between text characters.",
                    key: variable.key + ".letterSpacing",
                    type: ["string", "number"],
                },
            ];
        },
    });
    let hadError = false;
    for await (const [file, result] of parser.parseFiles(
        srcDirectory,
        pattern
    )) {
        console.log(`Scanned ${file}`);
        const { errors, variables } = result;
        results.push(...variables);
        if (variables.length === 0) {
            console.log("No valid variables detected");
        } else {
            console.log("Found valid variables.");
            variables.forEach((variable) => {
                console.log(variable.key);
            });
        }

        if (errors.length > 0) {
            hadError = true;
            console.error(
                `There were errors parsing some variables in ${file}`
            );
            errors.forEach((err) => {
                console.error(`${err.filePath}:${err.line} - ${err.message}`);
            });
        }
    }

    // Write the file out.
    // Convert to JSON Schema.
    const schema = JsonSchemaConverter.convertVariables(results);
    console.log(`Writing JSON schema to ${outFile}`);
    fse.writeFileSync(outFile, JSON.stringify(schema, null, 4));

    return !hadError;
}
