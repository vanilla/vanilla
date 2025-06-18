/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license Proprietary
 */

import { Plugin } from "vite";
import { OutputBundle } from "rollup";
import { VANILLA_ROOT } from "./scripts/env";
import path from "path";
import fse from "fs-extra";

export type IVanillaManifestChunk = {
    type: "chunk";
    file: string;
    name?: string;
    isEntry?: boolean;
    isDynamicEntry?: boolean;
    sizeBytes: number;
    modules: Array<{
        moduleId: string;
        sizeBytes: number;
    }>;
    imports: string[];
    dynamicImports: string[];
    css?: string[];
    assets?: string[];
};

export type IVanillaManifestAsset = {
    type: "asset";
    file: string;
    sizeBytes: number;
};

export type IVanillaManifestItem = IVanillaManifestAsset | IVanillaManifestChunk;

export type IVanillaViteManifest = Record<string, IVanillaManifestItem>;

/**
 *
 * Vite plugin to write a manifest.json file with information about all the assets in the section.
 *
 * @param buildSection
 * @returns
 */
export const VanillaManifestPlugin = async (buildSection: string): Promise<Plugin<any>> => {
    const plugin: Plugin = {
        name: "vite-vanilla-manifest-plugin",
        enforce: "post",
        apply: "build",
        async writeBundle(options, bundle: OutputBundle) {
            const result: Record<string, IVanillaManifestItem> = {};
            for (const [chunkName, chunk] of Object.entries(bundle)) {
                if (chunk.type === "chunk") {
                    const extra: any = {};
                    const { viteMetadata } = chunk;
                    if (viteMetadata?.importedAssets && viteMetadata.importedCss.size > 0) {
                        extra.css = Array.from(viteMetadata.importedCss);
                    }
                    if (viteMetadata?.importedAssets && viteMetadata.importedAssets.size > 0) {
                        extra.assets = Array.from(viteMetadata.importedAssets);
                    }

                    const modules = Object.entries(chunk.modules).map(([moduleId, module]) => {
                        return {
                            moduleId: moduleId.replace(VANILLA_ROOT, ""),
                            sizeBytes: module.code ? byteSize(module.code) : module.renderedLength,
                        };
                    });

                    result[chunkName] = {
                        type: "chunk",
                        file: chunk.fileName,
                        name: chunk.name,
                        isEntry: chunk.isEntry,
                        isDynamicEntry: chunk.isDynamicEntry,
                        sizeBytes: byteSize(chunk.code),
                        modules,
                        imports: chunk.imports,
                        dynamicImports: chunk.dynamicImports,
                        ...extra,
                    };
                }

                if (chunk.type === "asset") {
                    result[chunkName] = {
                        type: "asset",
                        file: chunk.fileName,
                        sizeBytes: byteSize(chunk.source),
                    };
                }
            }

            const outpath = path.join(VANILLA_ROOT, "dist/v2", buildSection, ".vite/manifest.json");
            await fse.ensureDir(path.dirname(outpath));
            await fse.writeJson(outpath, result, { spaces: 4 });
        },
    };

    return plugin;
};

const byteSize = (contents: string | Uint8Array) => {
    if (typeof contents === "string") {
        return Buffer.byteLength(contents, "utf8");
    } else {
        return contents.length;
    }
};
