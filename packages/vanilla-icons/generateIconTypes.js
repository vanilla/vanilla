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
const jsdom = require("jsdom");

const iconDir = path.resolve(__dirname, "./icons");
const fileNames = fse.readdirSync(iconDir);

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

let svgDefs = [];

const iconData = Object.fromEntries(
    fileNames.map((fileName) => {
        const iconName = fileName.replace(".svg", "");
        const iconContents = fse.readFileSync(path.resolve(iconDir, fileName), "utf8");
        const parsedIcon = new jsdom.JSDOM(iconContents).window.document.querySelector("svg");
        if (!parsedIcon) {
            return [iconName, null];
        }

        svgDefs.push(
            `<symbol id="${iconName}">${parsedIcon.innerHTML.replace(
                new RegExp("(#555A62|#000000)", "g"),
                "currentColor",
            )}</symbol>`,
        );

        const attrs = parsedIcon.getAttributeNames().reduce((acc, name) => {
            return { ...acc, [name]: parsedIcon.getAttribute(name) };
        }, {});
        delete attrs.xmlns;
        if (attrs.style) {
            const styleItems = attrs.style.split(";");
            attrs.style = styleItems.reduce((acc, item) => {
                const [key, value] = item.split(":");
                return { ...acc, [key]: value };
            }, {});
        }

        if (attrs.strokewidth) {
            attrs.strokeWidth = attrs.strokewidth;
            delete attrs.strokewidth;
        }

        return [iconName, attrs];
    }),
);

let resultJs = `
/**
 * @copyright 2009-${new Date().getFullYear()} Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

export type IconData = {
    height: string | null;
    width: string | null;
    viewBox: string | null;
    [key: string]: any;
}

export const coreIconsData = ${JSON.stringify(iconData, null, 4)};

export type IconType = keyof typeof coreIconsData;
`;

const filePath = path.resolve(__dirname, "./src/IconType.ts");
resultJs = prettier.format(resultJs, { ...vanillaPrettier, filepath: filePath });

fse.writeFileSync(filePath, resultJs);

const svgDefsResult = `
<svg style="display: none;">
    <defs>
        ${svgDefs.join("")}
    </defs>
</svg>
`;

const svgFilePath = path.resolve(__dirname, "../../resources/views/svg-symbols.html");
fse.writeFileSync(svgFilePath, svgDefsResult);
