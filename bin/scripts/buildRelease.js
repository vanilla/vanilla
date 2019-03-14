/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

// @ts-check

const shell = require("shelljs");
const fs = require("fs");
const path = require("path");
const { promptVersion, vanillaPath, printTitle, promptBranch } = require("./utils");

const VANILLA_REPO = "https://github.com/vanilla/vanilla.git";
const PHING_PATH = vanillaPath("vendor/bin/phing");
const BUILD_DIR = vanillaPath("build");
const XML_PATH = "build/build.xml";
const BUILD_XML = vanillaPath(XML_PATH);
const TEMP_DIR = vanillaPath("build/temp");

async function run() {
    const newVersion = await promptVersion();
    const branch = await promptBranch();

    printTitle("Preparing Fresh Vanilla Clone");
    const releasesPath = path.join(BUILD_DIR, "releases");
    if (!fs.existsSync(releasesPath)) {
        shell.mkdir(releasesPath);
    }

    if (!TEMP_DIR) {
        shell.cd(BUILD_DIR);
        shell.mkdir(TEMP_DIR);
    } else {
        shell.rm("-rf", TEMP_DIR);
        shell.mkdir(TEMP_DIR);
    }
    shell.cd(TEMP_DIR);
    console.log(`Cloning new copy of vanilla into ${TEMP_DIR}`);
    const cloneResult = shell.exec(`git clone --single-branch --branch ${branch} ${VANILLA_REPO} --depth 1`);
    if (cloneResult.code !== 0) {
        console.error(`Failed to clone branch ${branch}`);
        process.exit(cloneResult.code);
    }

    const clonedDir = path.join(TEMP_DIR, "vanilla");
    shell.cd(clonedDir);
    printTitle("Installing dependencies & Building");
    shell.exec("composer install --no-dev --optimize-autoloader");
    const buildDir = path.join(TEMP_DIR, "vanilla/build");
    shell.cd(buildDir);
    shell.exec(`env version=${newVersion} ${PHING_PATH}`);
}

run();
