/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */
// @ts-check

const prompts = require("prompts");
const path = require("path");
const shell = require('shelljs');
const chalk = require("chalk").default;

const ROOT = path.resolve(__dirname, "../../");

const vanillaPath = (...pathArgs) => path.resolve(ROOT, ...pathArgs);
function printTitle(title) {
    console.log(`\n==================== ${title} ====================`);
}

async function promptRequiredText(prompt) {
    const { newVersion } = await prompts({
        type: "text",
        name: "newVersion",
        message: chalk.green(prompt),
    });

    if (!newVersion) {
        shell.exit(0);
    }

    const { shouldCont } = await prompts({
        type: "confirm",
        name: "shouldCont",
        message: `You entered ${chalk.green(newVersion)}. Would you like to proceed?`,
    });

    if (!shouldCont) {
        console.log(chalk.red("Exiting"));
        shell.exit(0);
    }

    return newVersion;
}

async function promptVersion() {
    return promptRequiredText("What version would you like to use?")
}

async function promptBranch() {
    return promptRequiredText("What branch would you like to build from?")
}

module.exports = {
    promptVersion,
    ROOT,
    vanillaPath,
    printTitle,
    promptBranch,
}