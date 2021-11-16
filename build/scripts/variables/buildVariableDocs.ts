/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import {
    IVariable,
    IVariableGroup,
    JsonSchemaFlatAdapter,
    JsonSchemaNestedAdapter,
    VariableParser,
} from "@vanilla/variable-parser";
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
import { contentBoxesExpander } from "./contentBoxesExpander";
import { buttonExpander } from "./buttonExpander";
import { tagPresetExpander } from "./tagPresetExpander";
import { contributionItemsExpander } from "./contributionItemsExpander";
import { stackedListExpander } from "./stackedListExpander";

const parser = VariableParser.create()
    .addTypeExpander(fontExpander)
    .addTypeExpander(spacingExpander)
    .addTypeExpander(borderExpander)
    .addTypeExpander(clickableExpander)
    .addTypeExpander(backgroundExpander)
    .addTypeExpander(boxExpander)
    .addTypeExpander(contentBoxesExpander)
    .addTypeExpander(buttonExpander)
    .addTypeExpander(tagPresetExpander)
    .addTypeExpander(contributionItemsExpander)
    .addTypeExpander(stackedListExpander);

const pattern = "**/*{Styles,styles,variables,Variables,Vars}.{ts,tsx}";

async function buildVariableDocs() {
    printSection("Parsing Variables");
    print(`Root directory: ${chalk.yellow(VANILLA_ROOT)}`);
    print(`File pattern: ${chalk.yellow(pattern)}`);
    print("");

    const resultVars: IVariable[] = [];
    const resultGroups: IVariableGroup[] = [];

    const noVarFiles: string[] = [];
    let hadError = false;
    for await (const [file, result] of parser.parseFiles(VANILLA_ROOT, pattern)) {
        const { errors, variables, variableGroups } = result;
        resultGroups.push(...variableGroups);
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

    // Write the files out.
    // Convert to JSON Schema.
    if (hadError) {
        printSection("Process Failed");
        printError("Refusing to write file due encountered parsing errors.");
        process.exit(1);
    } else {
        printSection(`Writing JSON schema`);
        print(`Variable Count: ${chalk.green(resultVars.length.toString())}`);
        const outFlat = path.resolve(DIST_DIRECTORY, "variable-schema.json");
        const outNested = path.resolve(DIST_DIRECTORY, "variable-schema-nested.json");
        print(`Output Path (flat): ${chalk.green(outFlat)}`);
        print(`Output Path (nested): ${chalk.green(outNested)}`);

        const flatAdapter = new JsonSchemaFlatAdapter(resultVars, resultGroups);
        fs.writeFileSync(outFlat, JSON.stringify(flatAdapter.asJsonSchema(), null, 4));

        const nestedAdapter = new JsonSchemaNestedAdapter(resultVars, resultGroups);
        fs.writeFileSync(outNested, JSON.stringify(nestedAdapter.asJsonSchema(), null, 4));
    }
}

void buildVariableDocs();
