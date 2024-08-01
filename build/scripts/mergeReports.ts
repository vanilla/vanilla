/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

import { execSync } from "child_process";
import fse from "fs-extra";
import path from "path";
import { VANILLA_ROOT } from "./env";
import { print } from "./utility/utils";

const COVERAGE_ROOT_DIR = path.join(VANILLA_ROOT, "coverage");
const REPORTS_DIR = path.join(VANILLA_ROOT, "coverage/all-reports");
const MERGED_COVERAGE_DIR = path.join(VANILLA_ROOT, "coverage/merged");

// Get the contents of a path
function getPathContent(path: string) {
    return fse.readdirSync(path);
}

// Run some commands synchronously
function run(commands: string[]) {
    commands.forEach((command) => execSync(command, { stdio: "inherit" }));
}

// Create or empty some directories
function prepareDirectories() {
    fse.emptyDirSync(REPORTS_DIR);
    fse.emptyDirSync(".nyc_output");
    fse.emptyDirSync(MERGED_COVERAGE_DIR);
    print("Directories prepared");
}

// Move all coverage files into a the all-reports directory
function moveReports() {
    const subDirectories = getPathContent(COVERAGE_ROOT_DIR).filter((directoryName) => directoryName !== "all-reports");
    subDirectories.forEach((subDirectory) => {
        const subDirPath = path.join(COVERAGE_ROOT_DIR, subDirectory);
        print(`Moving coverage files in: ${subDirPath}`);
        // We only care about json files here
        fse.readdirSync(subDirPath).forEach((file) => {
            if (path.extname(file) === ".json") {
                fse.copyFileSync(
                    path.join(COVERAGE_ROOT_DIR, subDirectory, file),
                    `${REPORTS_DIR}/from-${subDirectory}.json`,
                );
            }
        });
    });
}

// Remove the unneeded directories and its content
function cleanupDirectories() {
    fse.remove(REPORTS_DIR);
    fse.remove(".nyc_output");
    print(`Directories cleaned`);
}

function mergeReports() {
    prepareDirectories();
    moveReports();
    run([
        `nyc merge ${REPORTS_DIR} && mv coverage.json .nyc_output/out.json`,
        `nyc report --reporter json --reporter lcov --report-dir ${MERGED_COVERAGE_DIR}`,
    ]);
    cleanupDirectories();
}

mergeReports();
