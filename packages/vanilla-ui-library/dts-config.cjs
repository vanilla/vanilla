// @ts-check

const path = require("path");
const fs = require("fs");

const VANILLA_ROOT = path.resolve(__dirname, "../../");
const packageRoot = path.resolve(__dirname);

/** @type import('dts-bundle-generator/config-schema').ConfigEntryPoint[] */
const entries = [];

/** @type import('dts-bundle-generator/config-schema').BundlerConfig */
const config = {
    compilationOptions: {
        preferredConfigPath: path.join(VANILLA_ROOT, "./tsconfig.dts.json"),
    },

    entries: [
        {
            filePath: path.join(packageRoot, "index.ts"),
            outFile: path.join(packageRoot, "dist", "index.d.ts"),
            noCheck: true,
            output: {
                inlineDeclareGlobals: true,
                exportReferencedTypes: true,
                sortNodes: false,
            },
        },
    ],
};

module.exports = config;
