#!/usr/bin/env node

/*!
 * @author Isis (igraziatto) Graziatto <isis.g@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

const path = require("path");
const fse = require("fs-extra");
const utils = require("./utils");

const TOOL_ROOT = path.resolve(__dirname, "../");
const DEST = path.resolve(process.cwd());

const themeKey = process.argv[2];
const themeName = process.argv[3];

if (!utils.validateArgs(themeKey, themeName)) {
    console.error('Install command should be followed by the theme-key and "Theme Name"');
    process.exit(1);
}

try {
    //copy addon.json
    fse.copyFileSync(path.resolve(TOOL_ROOT, "addon.json"), path.resolve(DEST, "addon.json"));
    fse.readFile(path.resolve(DEST, "addon.json"), 'utf8', function (err,data) {

        data = data.replace(/theme-boilerplate/g, themeKey);
        data = data.replace(/Theme Boilerplate/g, themeName);

        fse.writeFileSync(path.resolve(DEST, "addon.json"), data);
    });

    //copy README.md and swap theme name
    fse.copyFileSync(path.resolve(TOOL_ROOT, "setup/src/README.md"), path.resolve(DEST, "README.md"));
    fse.readFile(path.resolve(DEST, "README.md"), 'utf8', function (err,data) {

        data = data.replace(/Vanilla Theme Boilerplate/g, themeName);

        fse.writeFileSync(path.resolve(DEST, "README.md"), data);
    });

    //copy design/ dir
    fse.copySync(path.resolve(TOOL_ROOT, "design"), path.resolve(DEST, "design"));

    //create js/ dir
    fse.mkdirSync(path.resolve(DEST, "js"));

    //copy screenshot.png
    fse.copyFileSync(path.resolve(TOOL_ROOT, "screenshot.png"), path.resolve(DEST, "screenshot.png"));

    //create src/ dir
    fse.mkdir(path.resolve(DEST, "src"), function (err) {

        //create scss folder sctructure
        fse.mkdirSync(path.resolve(DEST, "src/scss"));
        fse.mkdirSync(path.resolve(DEST, "src/scss/base"));
        fse.mkdirSync(path.resolve(DEST, "src/scss/components"));
        fse.mkdirSync(path.resolve(DEST, "src/scss/pages"));
        fse.mkdirSync(path.resolve(DEST, "src/scss/sections"));

        //copy custom.scss
        fse.copyFileSync(path.resolve(TOOL_ROOT, "setup/src/custom.scss"), path.resolve(DEST, "src/scss/custom.scss"));

        //copy _variables.scss
        fse.copyFileSync(path.resolve(TOOL_ROOT, "src/scss/base/_variables.scss"), path.resolve(DEST, "src/scss/base/_variables.scss"));

        //create js folder
        fse.mkdirSync(path.resolve(DEST, "src/js"));

        //copy index.js
        fse.copyFileSync(path.resolve(TOOL_ROOT, "setup/src/index.js"), path.resolve(DEST, "src/js/index.js"));
    });

    //copy views/ dir
    fse.copySync(path.resolve(TOOL_ROOT, "views"), path.resolve(DEST, "views"));

    //copy settings/ dir
    fse.copySync(path.resolve(TOOL_ROOT, "settings"), path.resolve(DEST, "settings"));

    console.log("Boilerplate successfully installed!");

} catch (err) {
    console.error(err.message);
}
