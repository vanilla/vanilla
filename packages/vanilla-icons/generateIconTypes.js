/**
 * Script for generating the various icon types from the SVGs in the `icons` folder.
 *
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

// @ts-check

const fse = require("fs-extra");
const path = require("path");
const prettier = require("prettier");
const vanillaPrettier = require("@vanilla/prettier-config");

const fileNames = fse.readdirSync(path.resolve(__dirname, "./icons"));

const svgsByPrefix = {};

fileNames.forEach((fileName) => {
    const firstPiece = fileName.split("-")[0];
    svgsByPrefix[firstPiece] = svgsByPrefix[firstPiece] || [];
    svgsByPrefix[firstPiece].push(fileName.replace(".svg", ""));
});

/**
 * @param {string} prefix
 */
function getPrefixTypeName(prefix) {
    const uppered = prefix[0].toUpperCase() + prefix.slice(1);
    return uppered + "IconType";
}

let resultJs = `
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

${Object.entries(svgsByPrefix)
    .map(([prefix, svgs]) => {
        const typeName = getPrefixTypeName(prefix);
        const types = svgs.map((svg) => `\n    | "${svg}"`).join("");

        const result = `
type ${typeName} =${types}
;`;
        return result;
    })
    .join("\n")}

export type IconType =${Object.keys(svgsByPrefix)
    .map((key) => `\n    | ${getPrefixTypeName(key)}`)
    .join("")}
;
`;

const filePath = path.resolve(__dirname, "./src/IconType.ts");
resultJs = prettier.format(resultJs, { ...vanillaPrettier, filepath: filePath });

fse.writeFileSync(filePath, resultJs);
