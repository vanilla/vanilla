/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// @ts-check

const chalk = require("chalk").default;
const fs = require("fs");
const { promisify } = require("util");
const readFile = promisify(fs.readFile);
const writeFile = promisify(fs.writeFile);
const { vanillaPath, promptVersion, ROOT } = require("./utils");

const filesToAlter = [
    vanillaPath("applications/vanilla/addon.json"),
    vanillaPath("applications/dashboard/addon.json"),
    vanillaPath("applications/conversations/addon.json"),
];

async function run() {
    const newVersion = await promptVersion()

    for (const file of filesToAlter) {
        const prettyName = file.replace(ROOT, "");
        process.stdout.write(`Updating file ${chalk.yellow(prettyName)} `);
        try {
            const fileContent = await readFile(file);
            const value = JSON.parse(fileContent.toString());
            if (typeof value === "object") {
                value.version = newVersion;
                await writeFile(file, JSON.stringify(value, null, 4));
                console.log(chalk.greenBright(`✓`));
            }
        } catch (e) {
            console.error(chalk.red("✖"));
        }
    }
}

run();
