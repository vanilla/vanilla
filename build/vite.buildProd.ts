/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

import { copyMonacoEditorModule } from "build/scripts/utility/moduleUtils";
import path from "path";
import { visualizer } from "rollup-plugin-visualizer";
import * as tmp from "tmp";
import { build, PluginOption } from "vite";
import { DYNAMIC_ENTRY_DIR_PATH, LIBRARY_SRC_DIRECTORY, DIST_DIRECTORY } from "./scripts/env";
import EntryModel from "./scripts/utility/EntryModel";
import { printSection } from "./scripts/utility/utils";
import { makeViteBuildConfig } from "./vite.makeBuildConfig";
import "./vite.buildLegacyDashboard";
// @ts-check

run();

async function run() {
    let buildSections = process.env.BUILD_SECTIONS?.split(",") ?? [];
    if (buildSections.length === 0) {
        buildSections = ["admin", "admin-new", "forum", "layouts", "knowledge"];
    }
    const entryModel = new EntryModel();

    for (const section of buildSections) {
        printSection("Building section: " + section);
        const outFile = path.resolve(DYNAMIC_ENTRY_DIR_PATH, `${section}.html`);
        entryModel.synthesizeHtmlEntry(outFile, [section]);

        let config = makeViteBuildConfig(outFile);
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
                plugins: [...(config.plugins ?? []), makeVisualizerPlugin(section)],
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

function makeVisualizerPlugin(section: string): any {
    const tmpobj = tmp.dirSync();
    const filename = path.join(tmpobj.name, `${section} - stats.html`);
    return {
        ...visualizer({
            open: true,
            filename,
            title: `Analyze - ${section}`,
            brotliSize: false, // Bun doesn't currently support brotli. https://github.com/oven-sh/bun/issues/267
            gzipSize: true,
        }),
        enforce: "post",
    };
}
