/**
 * @author Maneesh Chiba <mchiba@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

import { labelize } from "@vanilla/utils";
import fse from "fs-extra";
import path from "path";

const source = process.argv[2];
const destination = process.argv[3];
const TAB = "    ";
const INDENT_1 = `${TAB}${TAB}${TAB}`;
const INDENT_2 = `${TAB}${TAB}${TAB}${TAB}`;

const header = `dataSources:
    RENAME_ME:
        name: "RENAME_ME"
        properties:`;

function readFile(path) {
    const scriptContent = fse.readFileSync(path, "utf8");
    try {
        return JSON.parse(scriptContent);
    } catch (error) {
        console.error(`Error parsing JSON from ${path}`);
        return null;
    }
}

function makeYMLFromFields(data, prefix = "") {
    const hasPrefix = prefix.length > 0;
    const keys = Object.keys(data);
    const yml = keys.reduce((acc, key) => {
        if (data[key] instanceof Object) {
            const nestedYml = makeYMLFromFields(data[key], `${hasPrefix ? prefix + "." : ""}${key}`);
            return `${acc}${nestedYml}`;
        }
        return `${acc}\n${INDENT_1}${hasPrefix ? prefix + "." : ""}${key}:\n${INDENT_2}name: "${labelize(
            key,
        )}"\n${INDENT_2}type: string\n${INDENT_2}hidden: true`;
    }, "");
    return yml;
}

function main() {
    if (!process.argv[2] || !process.argv[3] || process.argv[2] === "--help") {
        // eslint-disable-next-line
        console.log(
            "Please provide a source and destination file\nconvertKeenExtractToCatalog.ts <source> <destination>",
        );
    } else {
        const file = readFile(path.resolve(source));
        const yml = makeYMLFromFields(file);
        const fileContent = `${header}\n${yml}`;
        fse.writeFileSync(destination, fileContent);
    }
}

main();
