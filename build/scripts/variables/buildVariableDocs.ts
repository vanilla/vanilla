/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { JsonSchemaConverter, VariableParser } from "@vanilla/variable-parser";
import chalk from "chalk";
import fs from "fs-extra";
import path from "path";
import { boxExpander } from "./boxExpander";
import { DIST_DIRECTORY, VANILLA_ROOT } from "../env";
import { print, printError, printSection } from "../utility/utils";
import { backgroundExpander } from "./backgroundExpander";
import { borderExpander } from "./borderExpander";
import { clickableExpander } from "./clickableExpander";
import { fontExpander } from "./fontExpander";
import { spacingExpander } from "./spacingExpander";

const parser = VariableParser.create()
    .addTypeExpander(fontExpander)
    .addTypeExpander(spacingExpander)
    .addTypeExpander(borderExpander)
    .addTypeExpander(clickableExpander)
    .addTypeExpander(backgroundExpander)
    .addTypeExpander(boxExpander);

const pattern = "**/*{Styles,styles,variables,Variables}.{ts,tsx}";

async function buildVariableDocs() {
    printSection("Parsing Variables");
    print(`Root directory: ${chalk.yellow(VANILLA_ROOT)}`);
    print(`File pattern: ${chalk.yellow(pattern)}`);
    print("");

    const resultVars: any[] = [];

    const noVarFiles: string[] = [];
    let hadError = false;
    for await (const [file, result] of parser.parseFiles(VANILLA_ROOT, pattern)) {
        const { errors, variables } = result;
        resultVars.push(...variables);
        if (variables.length === 0) {
            noVarFiles.push(file);
        } else {
            print(`Scanned ${chalk.yellow(file)} - ${chalk.green(`${variables.length} variables.`)}`);
            // Maybe once we add a verbose mode?
            // variables.forEach((variable) => {
            //     print(variable.key);
            // });
        }

        if (errors.length > 0) {
            hadError = true;
            console.error(chalk.red(`There were errors parsing some variables in ${chalk.bold.white(file)}`));
            errors.forEach((err) => {
                console.error(
                    `${chalk.white(err.filePath)}:${chalk.yellow(err.line.toString())} - ${chalk.red(err.message)}`,
                );
            });
        }
    }

    if (noVarFiles.length > 0) {
        printSection("Empty Files");
        print(`There were ${chalk.bold.yellow(noVarFiles.length + " files scanned with no documented variables.")}`);

        // Maybe with a verbose mode?
        // print("The following files were scanned, but no documented variables were detected");
        // noVarFiles.forEach((file) => {
        //     print(chalk.yellow(file));
        // });
    }

    // Write the file out.
    // Convert to JSON Schema.
    if (hadError) {
        printSection("Process Failed");
        printError("Refusing to write file due encountered parsing errors.");
        process.exit(1);
    } else {
        const schema = JsonSchemaConverter.convertVariables(resultVars);
        printSection(`Writing JSON schema`);
        const out = path.resolve(DIST_DIRECTORY, "variable-schema.json");
        print(`Variable Count: ${chalk.green(resultVars.length.toString())}`);
        print(`Output Path: ${chalk.green(out)}`);
        fs.writeFileSync(out, JSON.stringify(schema, null, 4));
    }
}

void buildVariableDocs();
