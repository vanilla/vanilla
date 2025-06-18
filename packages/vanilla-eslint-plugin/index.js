/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const { noUnconventionalImports } = require("./rules/noUnconventionalImports");
const { noCloudImportsInCore } = require("./rules/noCloudImportsInCore");
const { noStaticLoaddableImports } = require("./rules/noStaticLoadableImports");

module.exports = {
    rules: {
        [noUnconventionalImports.name]: noUnconventionalImports,
        [noCloudImportsInCore.name]: noCloudImportsInCore,
        [noStaticLoaddableImports.name]: noStaticLoaddableImports,
    },
};
