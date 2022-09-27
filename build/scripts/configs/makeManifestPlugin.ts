/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

const { WebpackManifestPlugin } = require("webpack-manifest-plugin");
import { notEmpty } from "@vanilla/utils";
import webpack from "webpack";

interface IManifest {
    [chunkName: string]: {
        filePath: string;
        size: number;
        chunkName: string;
        chunkReason: string;
        dependsOnAsyncChunks: string[];
    };
}

export function makeManifestPlugin() {
    return new WebpackManifestPlugin({
        // Pull out a bit more information about our dependant files.
        generate: (
            seed: IManifest,
            files: Array<{
                isAsset: boolean;
                isChunk: boolean;
                isInitial: boolean;
                isModuleAsset: boolean;
                name: string;
                path: string;
                chunk: webpack.Chunk;
            }>,
        ) => {
            const manifest: IManifest = {};
            for (const file of files) {
                try {
                    const findChunkNameForFileName = (fileName: string) => {
                        const foundFile = files.find((file) => {
                            return file.path.includes(fileName);
                        });
                        return foundFile?.name;
                    };
                    let asyncChunkNames =
                        file.name.endsWith(".js") && file.name.includes("library") && file.chunk
                            ? Array.from(file.chunk.getAllAsyncChunks())
                                  .filter((chunk: webpack.Chunk) => {
                                      // We only care about chunks that webpack split out dynamically that we aren't specifically aware of otherwise.
                                      return !chunk.name;
                                  })
                                  .flatMap((chunk: webpack.Chunk) => {
                                      return Array.from(chunk.files).map(findChunkNameForFileName).filter(notEmpty);
                                  })
                            : [];
                    asyncChunkNames = Array.from(new Set(asyncChunkNames));
                    manifest[file.name] = {
                        filePath: `${file.path}`,
                        chunkName: `${file.name}`,
                        size: file.chunk?.size() ?? 0,
                        chunkReason: file.chunk?.chunkReason ?? "unknown",
                        dependsOnAsyncChunks: asyncChunkNames,
                    };
                } catch (err) {
                    console.error(err, file);
                }
            }
            return manifest;
        },
    });
}
