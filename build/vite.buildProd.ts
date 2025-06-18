/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { copyMonacoEditorModule } from "build/scripts/utility/moduleUtils";
import path from "path";
import { build } from "vite";
import { DYNAMIC_ENTRY_DIR_PATH, LIBRARY_SRC_DIRECTORY, DIST_DIRECTORY } from "./scripts/env";
import EntryModel from "./scripts/utility/EntryModel";
import { printSection } from "./scripts/utility/utils";
import { makeViteBuildConfig } from "./vite.makeBuildConfig";
import "./vite.buildLegacyDashboard";
import { minifyScripts } from "./scripts/minifyLegacyScripts";
import { VanillaChunkDebuggerPlugin } from "build/VanillaChunkDebuggerPlugin";
import { codecovVitePlugin } from "@codecov/vite-plugin";

void run();

async function run() {
    let buildSections = process.env.BUILD_SECTIONS?.split(",") ?? [];
    const allBuildSections = ["admin", "admin-new", "forum", "layouts", "knowledge"];
    if (buildSections.length === 0) {
        buildSections = ["admin", "admin-new", "forum", "layouts", "knowledge"];
    }

    if (buildSections.length === allBuildSections.length) {
        minifyScripts();
    }

    const entryModel = new EntryModel();

    for (const section of buildSections) {
        printSection("Building section: " + section);
        const outFile = path.resolve(DYNAMIC_ENTRY_DIR_PATH, `${section}.html`);
        entryModel.synthesizeHtmlEntry(outFile, [section]);

        let config = makeViteBuildConfig(section, outFile);
        config = {
            ...config,
            define: {
                ...config.define,
                "process.env.NODE_ENV": '"production"',
                "process.env.IS_WEBPACK": true,
            },
            mode: "production",
            build: {
                ...config.build,
                outDir: `./dist/v2/${section}`,
                watch: false as any,
            },
            server: undefined,
        };

        if (process.env.BUILD_ANALYZE) {
            config = {
                ...config,
                plugins: [...(config.plugins ?? []), VanillaChunkDebuggerPlugin()],
            };
        }

        if (process.env.CODECOV_TOKEN) {
            config = {
                ...config,
                plugins: [
                    ...(config.plugins ?? []),
                    codecovVitePlugin({
                        enableBundleAnalysis: true,
                        bundleName: section,
                        uploadToken: process.env.CODECOV_TOKEN,
                        telemetry: false,
                    }),
                ],
            };
        }

        await build(config);
    }

    // Now build the embed section
    await build({
        build: {
            manifest: true,
            rollupOptions: {
                input: path.resolve(LIBRARY_SRC_DIRECTORY, "embed/modernEmbed.remote.ts"),
            },
            cssCodeSplit: false,
            assetsDir: "",
            outDir: path.join(DIST_DIRECTORY, "embed"),
        },
    });
    copyMonacoEditorModule();
}
