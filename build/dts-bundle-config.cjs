// @ts-check

const path = require("path");
const fs = require("fs");
const { getVanillaInjectables } = require("./scripts/utility/vanillaSrcDirs.cjs");

const VANILLA_ROOT = path.resolve(__dirname, "..");

/** @type import('dts-bundle-generator/config-schema').ConfigEntryPoint[] */
const entries = [];

for (const injectable of getVanillaInjectables()) {
    const relativePath = path.relative(VANILLA_ROOT, injectable);
    const baseName = path.basename(relativePath);
    const outfileName = baseName.replace(/\.injectable\.(ts|tsx)$/, ".d.ts");

    const outPath = path.join(VANILLA_ROOT, "dist/v2/injectables", outfileName);

    entries.push({
        filePath: injectable,
        outFile: outPath,
        noCheck: true,
        libraries: {
            inlinedLibraries: ["@tanstack/react-query", "@tanstack/query-core", "axios"],
        },
        output: {
            inlineDeclareGlobals: true,
            exportReferencedTypes: true,
            sortNodes: false,
        },
    });
}

/** @type import('dts-bundle-generator/config-schema').BundlerConfig */
const config = {
    compilationOptions: {
        preferredConfigPath: path.join(VANILLA_ROOT, "./tsconfig.dts.json"),
    },

    entries: entries,
};

module.exports = config;
